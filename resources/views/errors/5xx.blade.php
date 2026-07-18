@php
    $statusCode =
        isset($exception) && method_exists($exception, 'getStatusCode') ? (string) $exception->getStatusCode() : '5xx';
@endphp

@include('errors.http', [
    'status' => $statusCode,
    'title' => 'Falha do servidor',
    'headline' => 'O sistema encontrou um erro ao processar a requisição.',
    'message' => 'O problema pode ser temporário. Tente novamente em alguns minutos.',
    'tip' => 'Se persistir, informe ao suporte o horário e o caminho acessado.',
    'tag' => 'Servidor',
])
