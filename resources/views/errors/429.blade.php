@include('errors.http', [
    'status' => '429',
    'title' => 'Muitas tentativas',
    'headline' => 'Você fez requisições em alta frequência.',
    'message' => 'O sistema aplicou uma pausa curta para proteger a operação e manter estabilidade.',
    'tip' => 'Aguarde alguns instantes antes de tentar de novo.',
    'tag' => 'Limite temporário',
])
