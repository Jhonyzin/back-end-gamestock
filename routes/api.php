<?php

/**
 * TABELA DE ROTAS DA API (prefixo /api é removido pelo Request)
 * Formato: [método, caminho, Controller, ação, nível de acesso]
 * Acesso: 'publico' (qualquer um), 'login' (usuário logado), 'admin' (ADMINISTRADOR)
 * Atenção: rotas fixas (ex.: /jogos/destaques) vêm ANTES de /jogos/{id}.
 */
return [
    // ----- Status (não usa o banco) -----
    ['GET',    '/',              'StatusController', 'status',    'publico'],

    // ----- Autenticação -----
    ['POST',   '/auth/cadastro', 'AuthController', 'cadastrar', 'publico'],
    ['POST',   '/auth/login',    'AuthController', 'login',     'publico'],
    ['POST',   '/auth/logout',   'AuthController', 'logout',    'login'],
    ['GET',    '/auth/me',       'AuthController', 'eu',        'login'],

    // ----- Perfil do usuário logado -----
    ['GET',    '/perfil',        'PerfilController', 'mostrar',        'login'],
    ['PUT',    '/perfil',        'PerfilController', 'atualizar',      'login'],
    ['PUT',    '/perfil/email',  'PerfilController', 'trocarEmail',    'login'],
    ['PUT',    '/perfil/senha',  'PerfilController', 'trocarSenha',    'login'],
    ['POST',   '/perfil/foto',   'PerfilController', 'enviarFoto',     'login'],
    ['DELETE', '/perfil/foto',   'PerfilController', 'removerFoto',    'login'],

    // ----- Jogos (vitrine) -----
    ['GET',    '/jogos',                    'JogoController', 'listar',        'publico'],
    ['GET',    '/jogos/destaques',          'JogoController', 'destaques',     'publico'],
    ['GET',    '/jogos/promocoes',          'JogoController', 'promocoes',     'publico'],
    ['GET',    '/jogos/mais-vendidos',      'JogoController', 'maisVendidos',  'publico'],
    ['GET',    '/jogos/lancamentos',        'JogoController', 'lancamentos',   'publico'],
    ['GET',    '/jogos/{id}',               'JogoController', 'detalhe',       'publico'],
    ['GET',    '/jogos/{id}/relacionados',  'JogoController', 'relacionados',  'publico'],
    ['GET',    '/jogos/{id}/avaliacoes',    'AvaliacaoController', 'listar',   'publico'],
    ['POST',   '/jogos/{id}/avaliacoes',    'AvaliacaoController', 'salvar',   'login'],

    // ----- Listas públicas -----
    ['GET',    '/categorias',     'CategoriaController', 'listarCategorias',  'publico'],
    ['GET',    '/plataformas',    'CategoriaController', 'listarPlataformas', 'publico'],
    ['GET',    '/configuracoes',  'ConfiguracaoController', 'publicas',       'publico'],

    // ----- Lista de desejos -----
    ['GET',    '/desejos',            'DesejoController', 'listar',     'login'],
    ['POST',   '/desejos/{jogoId}',   'DesejoController', 'adicionar',  'login'],
    ['DELETE', '/desejos/{jogoId}',   'DesejoController', 'remover',    'login'],

    // ----- Cupons e cartões -----
    ['POST',   '/cupons/validar',               'CupomController',  'validar',      'login'],
    ['GET',    '/cartoes/identificar',          'CartaoController', 'identificar',  'login'],
    ['GET',    '/formas-pagamento',             'CartaoController', 'listar',       'login'],
    ['POST',   '/formas-pagamento',             'CartaoController', 'adicionar',    'login'],
    ['PUT',    '/formas-pagamento/{id}/padrao', 'CartaoController', 'tornarPadrao', 'login'],
    ['DELETE', '/formas-pagamento/{id}',        'CartaoController', 'remover',      'login'],

    // ----- Compra, biblioteca e histórico -----
    ['POST',   '/checkout',                     'CheckoutController', 'finalizar',  'login'],
    ['GET',    '/biblioteca',                   'CompraController',   'biblioteca', 'login'],
    ['GET',    '/compras',                      'CompraController',   'historico',  'login'],
    ['GET',    '/compras/itens/{itemId}/codigo','CompraController',   'codigo',     'login'],

    // ----- ADMIN: dashboard -----
    ['GET',    '/admin/dashboard',   'AdminDashboardController', 'resumo', 'admin'],

    // ----- ADMIN: jogos, imagens e vídeos -----
    ['GET',    '/admin/jogos',               'AdminJogoController', 'listar',        'admin'],
    ['POST',   '/admin/jogos',               'AdminJogoController', 'criar',         'admin'],
    ['GET',    '/admin/jogos/{id}',          'AdminJogoController', 'mostrar',       'admin'],
    ['PUT',    '/admin/jogos/{id}',          'AdminJogoController', 'atualizar',     'admin'],
    ['DELETE', '/admin/jogos/{id}',          'AdminJogoController', 'excluir',       'admin'],
    ['POST',   '/admin/jogos/{id}/imagens',  'AdminJogoController', 'adicionarImagem','admin'],
    ['PUT',    '/admin/imagens/{id}',        'AdminJogoController', 'atualizarImagem','admin'],
    ['DELETE', '/admin/imagens/{id}',        'AdminJogoController', 'removerImagem', 'admin'],
    ['POST',   '/admin/jogos/{id}/videos',   'AdminJogoController', 'adicionarVideo','admin'],
    ['DELETE', '/admin/videos/{id}',         'AdminJogoController', 'removerVideo',  'admin'],
    ['GET',    '/admin/marcas',              'AdminJogoController', 'marcas',        'admin'],
    ['GET',    '/admin/editoras',            'AdminJogoController', 'editoras',      'admin'],

    // ----- ADMIN: promoções -----
    ['GET',    '/admin/promocoes',       'AdminPromocaoController', 'listar',    'admin'],
    ['POST',   '/admin/promocoes',       'AdminPromocaoController', 'criar',     'admin'],
    ['GET',    '/admin/promocoes/{id}',  'AdminPromocaoController', 'mostrar',   'admin'],
    ['PUT',    '/admin/promocoes/{id}',  'AdminPromocaoController', 'atualizar', 'admin'],
    ['DELETE', '/admin/promocoes/{id}',  'AdminPromocaoController', 'excluir',   'admin'],

    // ----- ADMIN: pedidos (somente leitura) -----
    ['GET',    '/admin/pedidos',   'AdminPedidoController', 'listar', 'admin'],

    // ----- ADMIN: usuários -----
    ['GET',    '/admin/usuarios',       'AdminUsuarioController', 'listar',    'admin'],
    ['POST',   '/admin/usuarios',       'AdminUsuarioController', 'criar',     'admin'],
    ['GET',    '/admin/usuarios/{id}',  'AdminUsuarioController', 'mostrar',   'admin'],
    ['PUT',    '/admin/usuarios/{id}',  'AdminUsuarioController', 'atualizar', 'admin'],
    ['DELETE', '/admin/usuarios/{id}',  'AdminUsuarioController', 'excluir',   'admin'],

    // ----- ADMIN: avaliações (moderação) -----
    ['GET',    '/admin/avaliacoes',       'AdminAvaliacaoController', 'listar',  'admin'],
    ['DELETE', '/admin/avaliacoes/{id}',  'AdminAvaliacaoController', 'excluir', 'admin'],

    // ----- ADMIN: categorias e plataformas -----
    ['GET',    '/admin/categorias',        'AdminCategoriaController', 'listar',    'admin'],
    ['POST',   '/admin/categorias',        'AdminCategoriaController', 'criar',     'admin'],
    ['PUT',    '/admin/categorias/{id}',   'AdminCategoriaController', 'atualizar', 'admin'],
    ['DELETE', '/admin/categorias/{id}',   'AdminCategoriaController', 'excluir',   'admin'],
    ['GET',    '/admin/plataformas',       'AdminPlataformaController', 'listar',    'admin'],
    ['POST',   '/admin/plataformas',       'AdminPlataformaController', 'criar',     'admin'],
    ['PUT',    '/admin/plataformas/{id}',  'AdminPlataformaController', 'atualizar', 'admin'],
    ['DELETE', '/admin/plataformas/{id}',  'AdminPlataformaController', 'excluir',   'admin'],

    // ----- ADMIN: configurações do site -----
    ['GET',    '/admin/configuracoes',  'AdminConfiguracaoController', 'listar',    'admin'],
    ['PUT',    '/admin/configuracoes',  'AdminConfiguracaoController', 'atualizar', 'admin'],
];
