@include('errors.http', [
    'status' => '500',
    'title' => 'Falha interna',
    'headline' => 'Não foi possível concluir esta operação agora.',
    'message' => 'Detectamos uma falha interna ao processar sua solicitação.',
    'tip' =>
        'Tente novamente em alguns minutos. Se persistir, compartilhe o horário e o Request ID com o suporte.',
    'tag' => 'Falha interna',
])
