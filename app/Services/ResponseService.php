<?php

namespace App\Services;

class ResponseService
{

    /**
     * Default Responses.
     *
     * @return void
     */
    public static function default($config = array(), $id = null)
    {
        $route = $config['route'];
        switch ($config['type']) {
            case 'store':
                return [
                    'status' => true,
                    'msg'    => 'Dado inserido com sucesso',
                    'url'    => route($route)
                ];
                break;
            case 'show':
                return [
                    'status' => true,
                    'msg'    => 'Requisição realizada com sucesso',
                    'url'    => $id != null ? route($route, $id) : route($route)
                ];
                break;
            case 'update':
                return [
                    'status' => true,
                    'msg'    => 'Dados Atualizado com sucesso',
                    'url'    => $id != null ? route($route, $id) : route($route)
                ];
                break;
            case 'destroy':
                return [
                    'status' => true,
                    'msg'    => 'Dado excluido com sucesso',
                    'url'    => $id != null ? route($route, $id) : route($route)
                ];
                break;
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public static function exception($route, $id = null, $e)
    {
        switch ($e->getCode()) {
            case -403:
                return response()->json([
                    'status' => false,
                    'statusCode' => 403,
                    'error'  => $e->getMessage(),
                    'url'    => $id != null ? route($route, $id) : route($route)
                ], 403);
                break;
            case -404:
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'error'  => $e->getMessage(),
                    'url'    => $id != null ? route($route, $id) : route($route)
                ], 404);
                break;
            default:
                if (app()->bound('sentry')) {
                    $sentry = app('sentry');
                    $user = auth()->user();
                    if ($user) {
                        $sentry->user_context(['id' => $user->id, 'name' => $user->name]);
                    }
                    $sentry->captureException($e);
                }
                return response()->json([
                    'status' => false,
                    'statusCode' => 500,
                    'error'  => 'Problema ao realizar a operação.',
                    'url'    => $id != null ? route($route, $id) : route($route)
                ], 500);
                break;
        }
    }

    public static function customMessage($route, $id = null, $msg)
    {
        $status_code = $id != null ? 404 : 201;
        return response()->json([
            'status' => $status_code == 404 ? false : true,
            'statusCode' => $status_code,
            'error'  => $msg,
            'url'    => $id != null ? route($route, $id) : route($route)
        ], $status_code);
    }
}
