<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use ServeraCloud\Manual\Exceptions\ManualException;
use ServeraCloud\Manual\Services\ManualRepository;

final class SearchIndexController extends Controller {
    public function __invoke(ManualRepository $manual): JsonResponse {
        abort_unless((bool) config('manual.search.enabled', true), 404);

        try {
            return response()->json($manual->searchIndex());
        } catch (ManualException $exception) {
            report($exception);

            return response()->json([
                'message' => $this->publicErrorMessage($exception),
            ], 500);
        }
    }

    private function publicErrorMessage(ManualException $exception): string {
        if ((bool) config('app.debug', false)) {
            return $exception->getMessage();
        }

        return 'O indice de busca do manual nao pode ser carregado agora.';
    }
}
