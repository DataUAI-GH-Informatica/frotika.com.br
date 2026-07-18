@include('errors.http', [
    'status' => '401',
    'title' => 'Sessão necessária',
    'headline' => 'Você precisa entrar para acessar esta página.',
    'message' => 'A autenticação expirou ou esta área exige uma conta ativa para continuar.',
    'tip' => 'Entre novamente e repita a operação.',
    'tag' => 'Autenticação',
])
