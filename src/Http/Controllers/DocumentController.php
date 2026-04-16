<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use ServeraCloud\Manual\Exceptions\DocumentNotFoundException;
use ServeraCloud\Manual\Exceptions\ManualException;
use ServeraCloud\Manual\Services\ManualRepository;

final class DocumentController extends Controller {
    public function __invoke(ManualRepository $manual, ?string $path = null): Response {
        try {
            $page = $manual->page($path ?? '');
        } catch (DocumentNotFoundException) {
            $prefix  = (string) config('manual.route_prefix', 'manual');
            $homeUrl = $prefix !== '' ? url($prefix) : url('/');

            return response()->view('manual::not-found', [
                'siteTitle' => (string) config('manual.site_title', 'Documentation'),
                'homeUrl'   => $homeUrl,
            ], 404);
        } catch (ManualException $exception) {
            report($exception);

            return response()->view('manual::error', [
                'siteTitle' => (string) config('manual.site_title', 'Documentation'),
                'message' => $this->publicErrorMessage($exception),
            ], 500);
        }

        return response()->view((string) config('manual.view', 'manual::page'), [
            'page' => $page,
            'document' => $page->document,
            'navigation' => $page->navigation,
            'breadcrumbs' => $page->breadcrumbs,
            'previousPage' => $page->previous,
            'nextPage' => $page->next,
            'siteTitle' => $page->siteTitle,
            'searchEndpoint' => $page->searchEndpoint,
            'assetsEnabled' => (bool) config('manual.assets.enabled', true),
        ]);
    }

    private function publicErrorMessage(ManualException $exception): string {
        if ((bool) config('app.debug', false)) {
            return $exception->getMessage();
        }

        return 'O manual nao pode ser carregado agora. Consulte os logs da aplicacao para mais detalhes.';
    }
}
