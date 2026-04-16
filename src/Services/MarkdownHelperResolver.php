<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Stringable;
use UnitEnum;
use ServeraCloud\Manual\Data\ManualManifest;
use ServeraCloud\Manual\Exceptions\DocumentPublicPathNotFoundException;
use ServeraCloud\Manual\Exceptions\DocumentRouteNameNotFoundException;
use ServeraCloud\Manual\Exceptions\InvalidMarkdownHelperException;
use ServeraCloud\Manual\Exceptions\NamedRouteNotFoundException;
use Throwable;

final class MarkdownHelperResolver {
    public function __construct(
        private readonly Router $router,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function resolve(string $markdown, ManualManifest $manifest, string $documentPath): string {
        $normalized = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $normalized);
        $resolvedLines = [];
        $inFencedCodeBlock = false;
        $fenceCharacter = '';
        $fenceLength = 0;
        $inlineCodeTicks = null;

        foreach ($lines as $line) {
            if ($inFencedCodeBlock) {
                $resolvedLines[] = $line;

                if ($this->isClosingFence($line, $fenceCharacter, $fenceLength)) {
                    $inFencedCodeBlock = false;
                    $fenceCharacter = '';
                    $fenceLength = 0;
                }

                continue;
            }

            $openingFence = $inlineCodeTicks === null
                ? $this->openingFence($line)
                : null;

            if ($openingFence !== null) {
                $resolvedLines[] = $line;
                $inFencedCodeBlock = true;
                $fenceCharacter = $openingFence['character'];
                $fenceLength = $openingFence['length'];

                continue;
            }

            [$resolvedLine, $inlineCodeTicks] = $this->resolveLine(
                line: $line,
                manifest: $manifest,
                documentPath: $documentPath,
                inlineCodeTicks: $inlineCodeTicks,
            );

            $resolvedLines[] = $resolvedLine;
        }

        return implode("\n", $resolvedLines);
    }

    public function cacheFingerprint(): string {
        $namedRoutes = $this->router->getRoutes()->getRoutesByName();
        ksort($namedRoutes);
        $payload = [
            'named_routes' => [],
            'url_generator' => [
                'defaults' => $this->normalizeValue($this->urlGenerator->getDefaultParameters()),
                'origin' => $this->urlGeneratorOrigin(),
            ],
        ];

        foreach ($namedRoutes as $name => $route) {
            $methods = $route->methods();
            sort($methods);

            $payload['named_routes'][$name] = [
                'uri' => $route->uri(),
                'domain' => $route->getDomain() ?? '',
                'methods' => $methods,
                'defaults' => $this->normalizeValue($route->defaults),
                'wheres' => $this->normalizeValue($route->wheres),
                'secure' => $route->secure(),
            ];
        }

        return sha1(serialize($payload));
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function resolveLine(
        string $line,
        ManualManifest $manifest,
        string $documentPath,
        ?int $inlineCodeTicks = null,
    ): array {
        $resolved = '';
        $offset = 0;
        $length = strlen($line);

        while ($offset < $length) {
            if ($inlineCodeTicks !== null) {
                $delimiter = str_repeat('`', $inlineCodeTicks);
                $closingOffset = strpos($line, $delimiter, $offset);

                if ($closingOffset === false) {
                    $resolved .= substr($line, $offset);

                    return [$resolved, $inlineCodeTicks];
                }

                $endOffset = $closingOffset + $inlineCodeTicks;
                $resolved .= substr($line, $offset, $endOffset - $offset);
                $offset = $endOffset;
                $inlineCodeTicks = null;

                continue;
            }

            $nextCodeOffset = strpos($line, '`', $offset);
            $segment = $nextCodeOffset === false
                ? substr($line, $offset)
                : substr($line, $offset, $nextCodeOffset - $offset);

            $resolved .= $this->resolveTextSegment($segment, $manifest, $documentPath);

            if ($nextCodeOffset === false) {
                break;
            }

            $ticks = $this->backtickRunLength($line, $nextCodeOffset);
            $resolved .= str_repeat('`', $ticks);
            $offset = $nextCodeOffset + $ticks;
            $inlineCodeTicks = $ticks;
        }

        return [$resolved, $inlineCodeTicks];
    }

    private function resolveTextSegment(string $segment, ManualManifest $manifest, string $documentPath): string {
        return preg_replace_callback(
            '/\{\{(.*?)\}\}/',
            function (array $matches) use ($manifest, $documentPath): string {
                $expression = trim($matches[1]);

                if ($expression === '') {
                    return $matches[0];
                }

                if (! preg_match('/\A(route|doc|doc_public)\b/s', $expression)) {
                    return $matches[0];
                }

                return $this->resolveExpression($expression, $manifest, $documentPath);
            },
            $segment,
        ) ?? $segment;
    }

    private function resolveExpression(string $expression, ManualManifest $manifest, string $documentPath): string {
        if (! preg_match(
            '/\A(?<name>route|doc|doc_public)\s*\(\s*(?<quote>[\'"])(?<argument>(?:\\\\.|(?!\k<quote>).)*)\k<quote>\s*\)\z/s',
            $expression,
            $matches,
        )) {
            throw new InvalidMarkdownHelperException(sprintf(
                'Invalid Markdown helper "{{ %s }}" in "%s". Supported helpers accept exactly one string argument.',
                $expression,
                $documentPath,
            ));
        }

        $name = $matches['name'];
        $argument = stripcslashes($matches['argument']);

        return match ($name) {
            'route' => $this->resolveNamedRoute($argument, $expression, $documentPath),
            'doc' => $this->resolveDocumentRouteName($argument, $expression, $documentPath, $manifest),
            'doc_public' => $this->resolvePublicDocumentPath($argument, $expression, $documentPath, $manifest),
        };
    }

    private function resolveNamedRoute(string $routeName, string $expression, string $documentPath): string {
        if ($this->router->getRoutes()->getByName($routeName) === null) {
            throw new NamedRouteNotFoundException(sprintf(
                'The Laravel route "%s" referenced by "{{ %s }}" in "%s" does not exist.',
                $routeName,
                $expression,
                $documentPath,
            ));
        }

        try {
            return $this->urlGenerator->route($routeName);
        } catch (Throwable $exception) {
            throw new InvalidMarkdownHelperException(sprintf(
                'The Markdown helper "{{ %s }}" in "%s" could not be resolved: %s',
                $expression,
                $documentPath,
                $exception->getMessage(),
            ), previous: $exception);
        }
    }

    private function resolveDocumentRouteName(
        string $routeName,
        string $expression,
        string $documentPath,
        ManualManifest $manifest,
    ): string {
        $document = $manifest->documentForRouteName($routeName);

        if ($document === null) {
            throw new DocumentRouteNameNotFoundException(sprintf(
                'The manual document route_name "%s" referenced by "{{ %s }}" in "%s" does not exist.',
                $routeName,
                $expression,
                $documentPath,
            ));
        }

        return $this->manualDocumentUrl($document->routePath, $expression, $documentPath);
    }

    private function resolvePublicDocumentPath(
        string $routePath,
        string $expression,
        string $documentPath,
        ManualManifest $manifest,
    ): string {
        $normalizedPath = $this->normalizePublicPath($routePath);
        $document = $manifest->documentForPublicPath($normalizedPath);

        if ($document === null) {
            throw new DocumentPublicPathNotFoundException(sprintf(
                'The manual document public path "%s" referenced by "{{ %s }}" in "%s" does not exist.',
                $routePath,
                $expression,
                $documentPath,
            ));
        }

        return $this->manualDocumentUrl($document->routePath, $expression, $documentPath);
    }

    private function manualDocumentUrl(string $routePath, string $expression, string $documentPath): string {
        try {
            return $this->urlGenerator->route('manual.document', [
                'path' => $routePath === '' ? null : $routePath,
            ]);
        } catch (Throwable $exception) {
            throw new InvalidMarkdownHelperException(sprintf(
                'The Markdown helper "{{ %s }}" in "%s" could not be resolved: %s',
                $expression,
                $documentPath,
                $exception->getMessage(),
            ), previous: $exception);
        }
    }

    /**
     * @return array{character: string, length: int}|null
     */
    private function openingFence(string $line): ?array {
        if (! preg_match('/^\s{0,3}(`{3,}|~{3,})/', $line, $matches)) {
            return null;
        }

        return [
            'character' => $matches[1][0],
            'length' => strlen($matches[1]),
        ];
    }

    private function isClosingFence(string $line, string $character, int $length): bool {
        return (bool) preg_match(
            '/^\s{0,3}' . preg_quote(str_repeat($character, $length), '/') . $character . '*[ \t]*$/',
            $line,
        );
    }

    private function backtickRunLength(string $line, int $offset): int {
        $length = strlen($line);
        $runLength = 0;

        while ($offset + $runLength < $length && $line[$offset + $runLength] === '`') {
            $runLength++;
        }

        return $runLength;
    }

    private function urlGeneratorOrigin(): string {
        try {
            return rtrim($this->urlGenerator->to('/'), '/');
        } catch (Throwable) {
            return '';
        }
    }

    private function normalizeValue(mixed $value): mixed {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            $normalized = $value;

            if (! array_is_list($normalized)) {
                ksort($normalized);
            }

            foreach ($normalized as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item);
            }

            return $normalized;
        }

        if (is_object($value)) {
            return ['class' => $value::class];
        }

        return $value;
    }

    private function normalizePublicPath(string $routePath): string {
        $trimmed = trim(str_replace('\\', '/', $routePath), '/');

        if ($trimmed === '') {
            return '';
        }

        $segments = [];

        foreach (explode('/', $trimmed) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}
