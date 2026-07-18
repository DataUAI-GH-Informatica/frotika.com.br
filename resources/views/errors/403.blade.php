@include('errors.http', [
    'status' => '403',
    'title' => 'Acesso negado',
    'headline' => 'Esta ação não está liberada para o seu perfil.',
    'message' => 'Seu usuário não tem permissão para abrir este recurso.',
    'tip' => 'Se você acredita que deveria ter acesso, fale com o administrador da empresa.',
    'tag' => 'Permissão',
])
