<?php
function cms_default_settings(): array {
    return [
        'nome' => 'Bar Encontro dos Amigos',
        'seo_description' => 'Site do Bar Encontro dos Amigos. Cardápio, eventos, galeria e contato.',
        'url' => '',
        'whatsapp' => '+55 11 99999-0000',
        'instagram' => 'https://instagram.com/barencontrodosamigos.demo',
        'facebook' => 'https://facebook.com/barencontrodosamigos.demo',
        'email' => 'contato@barencontrodosamigos.demo',
        'telefone' => '(11) 4000-1234',
        'endereco' => 'Rua Exemplo, 123',
        'cidade' => 'Mogi das Cruzes - SP',
        'horario' => 'Ter a Dom • 17h às 00h',
        'hero_kicker' => 'BAR ENCONTRO DOS AMIGOS',
        'hero_line1' => 'SEU',
        'hero_line2' => 'PONTO DE',
        'hero_line3' => 'ENCONTRO',
        'sobre_titulo' => 'BAR ENCONTRO DOS AMIGOS',
        'sobre_texto' => 'O Bar Encontro dos Amigos nasceu para ser aquele lugar de boa comida, bebida gelada, música e resenha. Edite este texto pelo painel administrativo.',
        'demo_note' => 'Conteúdo demonstrativo. Edite tudo pelo painel administrativo.',
        'logo_image' => 'assets/img/logo.png',
        'hero_image' => 'assets/uploads/bar-ambiente-principal.jpeg',
    ];
}

function cms_default_events(): array {
    return [
        [
            'id' => 'evt_' . substr(md5('demo1'), 0, 10),
            'titulo' => 'Sexta entre Amigos',
            'data' => date('Y-m-d', strtotime('+7 days')),
            'hora' => '20:00',
            'descricao' => 'Evento demonstrativo para mostrar como a agenda ficará no site.',
            'imagem' => '',
            'destaque' => true,
            'ativo' => true,
            'created_at' => date('c'),
        ],
        [
            'id' => 'evt_' . substr(md5('demo2'), 0, 10),
            'titulo' => 'Happy Hour',
            'data' => date('Y-m-d', strtotime('+14 days')),
            'hora' => '18:00',
            'descricao' => 'Exemplo fictício que pode ser editado ou apagado pelo painel.',
            'imagem' => '',
            'destaque' => false,
            'ativo' => true,
            'created_at' => date('c'),
        ],
    ];
}

function cms_default_menu(): array {
    return [
        ['id'=>'menu_' . substr(md5('porcao'),0,10),'titulo'=>'Porção Encontro dos Amigos','categoria'=>'Porções','descricao'=>'Porção especial para compartilhar.','preco'=>'49,90','imagem'=>'','ativo'=>true,'created_at'=>date('c')],
        ['id'=>'menu_' . substr(md5('drink'),0,10),'titulo'=>'Drink Encontro','categoria'=>'Bebidas','descricao'=>'Drink assinatura da casa.','preco'=>'24,90','imagem'=>'','ativo'=>true,'created_at'=>date('c')],
    ];
}

function cms_default_gallery(): array {
    return [
        ['id'=>'gal_decoracao_arraia','titulo'=>'Decoração do bar','descricao'=>'Peça decorativa colorida que faz parte da identidade do ambiente.','imagem'=>'assets/uploads/bar-decoracao-arraia.jpeg','ativo'=>true,'created_at'=>date('c')],
        ['id'=>'gal_pitu_decoracao','titulo'=>'Decoração Pitú','descricao'=>'Garrafa decorativa Pitú presente no salão do bar.','imagem'=>'assets/uploads/bar-pitu-decoracao.jpeg','ativo'=>true,'created_at'=>date('c')],
        ['id'=>'gal_placa_proibido','titulo'=>'Detalhes do ambiente','descricao'=>'Placas e objetos que dão personalidade ao Bar Encontro dos Amigos.','imagem'=>'assets/uploads/bar-placa-proibido.jpeg','ativo'=>true,'created_at'=>date('c')],
        ['id'=>'gal_placa_pingas','titulo'=>'Frases do bar','descricao'=>'Uma das placas decorativas que fazem parte do clima descontraído do local.','imagem'=>'assets/uploads/bar-placa-pingas.jpeg','ativo'=>true,'created_at'=>date('c')],
        ['id'=>'gal_arte_parede','titulo'=>'Arte e decoração','descricao'=>'Quadros e peças artísticas que decoram as paredes do bar.','imagem'=>'assets/uploads/bar-arte-parede.jpeg','ativo'=>true,'created_at'=>date('c')],
        ['id'=>'gal_ambiente_principal','titulo'=>'O Bar Encontro dos Amigos','descricao'=>'Vista real do interior do bar e de parte da decoração do ambiente.','imagem'=>'assets/uploads/bar-ambiente-principal.jpeg','ativo'=>true,'created_at'=>date('c')],
    ];
}

