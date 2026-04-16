<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use ServeraCloud\Manual\Data\ParsedFrontMatter;
use ServeraCloud\Manual\Exceptions\InvalidFrontMatterException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class FrontMatterParser {
    public function parse(string $contents, string $documentPath): ParsedFrontMatter {
        if (! preg_match('/\A---\R(.*?)\R---\R?/s', $contents, $matches)) {
            return new ParsedFrontMatter([], $contents);
        }

        try {
            $attributes = Yaml::parse($matches[1]);
        } catch (ParseException $exception) {
            throw new InvalidFrontMatterException(sprintf(
                'Invalid YAML front matter in "%s": %s',
                $documentPath,
                $exception->getMessage(),
            ), previous: $exception);
        }

        if ($attributes === null) {
            $attributes = [];
        }

        if (! is_array($attributes)) {
            throw new InvalidFrontMatterException(sprintf(
                'The front matter for "%s" must be a YAML object.',
                $documentPath,
            ));
        }

        $normalized = $this->normalizeAttributes($attributes, $documentPath);

        return new ParsedFrontMatter(
            attributes: $normalized,
            body: substr($contents, strlen($matches[0])),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes, string $documentPath): array {
        foreach (['title', 'slug', 'route', 'description', 'route_name'] as $key) {
            if (array_key_exists($key, $attributes) && ! is_string($attributes[$key])) {
                throw new InvalidFrontMatterException(sprintf(
                    'The "%s" front matter for "%s" must be a string.',
                    $key,
                    $documentPath,
                ));
            }
        }

        if (array_key_exists('hidden', $attributes) && ! is_bool($attributes['hidden'])) {
            throw new InvalidFrontMatterException(sprintf(
                'The "hidden" front matter for "%s" must be a boolean.',
                $documentPath,
            ));
        }

        if (array_key_exists('order', $attributes)) {
            $order = $attributes['order'];

            if (! is_int($order) && ! (is_string($order) && preg_match('/^-?\d+$/', $order))) {
                throw new InvalidFrontMatterException(sprintf(
                    'The "order" front matter for "%s" must be an integer.',
                    $documentPath,
                ));
            }

            $attributes['order'] = (int) $order;
        }

        if (array_key_exists('route_name', $attributes)) {
            $routeName = trim((string) $attributes['route_name']);

            if ($routeName === '') {
                throw new InvalidFrontMatterException(sprintf(
                    'The "route_name" front matter for "%s" cannot be empty.',
                    $documentPath,
                ));
            }

            $attributes['route_name'] = $routeName;
        }

        return $attributes;
    }
}
