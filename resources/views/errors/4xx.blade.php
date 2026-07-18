@php
    $statusCode =
        isset($exception) && method_exists($exception, 'getStatusCode') ? (string) $exception->getStatusCode() : '4xx';
@endphp

@include('errors.http', [
    'status' => $statusCode,
    'title' => 'Requisição inválida',
    'headline' => 'A requisição não pode ser atendida neste formato.',
    'message' => 'Revise os dados enviados e tente novamente.',
    'tip' => 'Se o problema aparecer com frequência, acione o suporte com o horário da ocorrência.',
    'tag' => 'Cliente',
])
