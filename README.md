# Bar Encontro dos Amigos — versão 2

Site público e painel administrativo redesenhados. PHP continua responsável por dados, login e uploads. O frontend usa React 19, Motion Primitives, Watermelon UI, Radix UI e Tailwind CSS 4, com fontes locais DM Sans/Fraunces.

## Abrir no XAMPP ou Apache

1. Use PHP 8.2 ou superior, com a extensão fileinfo habilitada.
2. Extraia o ZIP em uma pasta como C:\xampp\htdocs\bar_encontro_dos_amigos.
3. Inicie o Apache e abra o endereço local dessa pasta.
4. Acesse a pasta admin/ e crie seu usuário e senha no primeiro acesso. Não existe credencial padrão.
5. Em “Informações do site”, configure os contatos, endereço, horários e textos reais.

O ZIP inclui assets/build completo. Não é necessário instalar Node.js para usar a versão compilada no XAMPP ou na hospedagem PHP.

Mantenha .htaccess, storage/.htaccess e assets/uploads/.htaccess. Apache deve aceitar essas regras e disponibilizar mod_rewrite e mod_authz_core. Dê ao processo PHP permissão de leitura/escrita em storage/ e assets/uploads/; não use permissões 777. Em Nginx, configure explicitamente o bloqueio de storage/, includes/, src/, tests/, node_modules/, database/ e arquivos de desenvolvimento. O servidor local PHP usa router.php para aplicar os bloqueios equivalentes.

## Desenvolver o frontend

Requisitos: Node.js 22.12+ ou Node 24 e npm.

    npm ci
    npm run build

Para trabalhar com reconstrução automática, em dois terminais:

    php -S 127.0.0.1:8080 router.php
    npm run dev

npm run dev observa os arquivos e recompila assets/build; não inicia um segundo site separado. Atualize o navegador após as mudanças. npm run build também executa a checagem TypeScript.

Neste ambiente da nuvem o PHP foi instalado sem alterar o sistema, em /workspace/tooling/php. Ative o comando com:

    export PATH="/workspace/tooling/bin:$PATH"
    cd /workspace/sitebar
    npm run serve

Use o servidor PHP somente para desenvolvimento. Em produção, use Apache/Nginx com PHP e HTTPS.

## O que mudou

- Home com identidade em verde escuro e âmbar, tipografia editorial, fotos reais e SVGs decorativos originais.
- Cardápio com categorias, busca e estados vazios.
- Agenda com páginas individuais de eventos.
- Galeria com ampliação, navegação entre fotos, teclado e retorno do foco.
- Contato com preparação de mensagem para WhatsApp; o visitante confirma o envio no aplicativo. Nenhuma mensagem é enviada automaticamente ou armazenada pelo site.
- Painel responsivo com visão geral, busca, edição, publicação/ocultação e exclusão confirmada de conteúdo.
- Gestão das informações, logotipo, foto principal, usuário e senha.
- Frontend público e painel separados em módulos de carregamento; fontes locais, imagens WebP e respeito à preferência de movimento reduzido.
- API PHP com autenticação por sessão, CSRF em todas as mutações, validação dos campos e uploads, erros estruturados, limitação de tentativas de login e invalidação de outras sessões ao trocar credenciais.
- Gravação atômica de JSON, com bloqueio em toda a transação para evitar perda de alterações concorrentes.
- Operações de exclusão e logout exigem POST; URLs antigas por GET não alteram dados.

TextMorph, do Motion Primitives, anima categorias e indicadores reais. ShimmerButton, do Watermelon UI, aparece nos formulários e ações do painel. Modal e foco são implementados com Radix UI. Os SVGs foram criados para o projeto como alternativa às formas exportadas pelo Haikei, que é um serviço online; não foi usada uma API ou versão local do Haikei. Veja THIRD_PARTY.md.

## Dados e compatibilidade

Os arquivos settings.json, menu.json, events.json e gallery.json foram preservados exatamente como vieram no ZIP. As fotos JPEG/PNG e o logotipo originais também permanecem; versões WebP foram adicionadas.

O projeto de origem contém contatos/endereço demonstrativos, dois itens de cardápio e dois eventos de exemplo. Esses conteúdos não foram inventados ou substituídos no redesign. Ajuste-os no painel, confirme os preços e remova o aviso demonstrativo quando os dados estiverem corretos.

As páginas index.php, cardapio.php, eventos.php, evento.php, galeria.php, sobre.php e contato.php funcionam, assim como os aliases vercardapio.php, vereventos.php e vergaleria.php. Os caminhos administrativos antigos continuam abrindo o novo painel.

Não foi necessário MySQL. database/schema.sql é uma referência herdada, não uma dependência ativa. Faça backups de storage/ e assets/uploads/ juntos. Nunca publique storage/admin.json em uma pasta acessível como arquivo estático. Crie o administrador antes de divulgar a instalação.

## Testes

    npm run check
    npm run test:backend
    npx playwright install chromium
    npm test

Os testes backend.py iniciam um servidor PHP temporário com dados descartáveis. Os testes Playwright também usam armazenamento separado e removem os uploads criados por eles. Eles não criam um administrador de demonstração na instalação real.

Verificações realizadas nesta entrega:
- Build de produção e tipos TypeScript.
- Sintaxe de todos os arquivos PHP.
- 41 verificações funcionais de API, autenticação, validação, publicação e concorrência.
- 3 cenários completos no navegador: navegação/filtros/galeria/contato, páginas em 320px e gestão administrativa com criação/edição/ocultação/exclusão/upload/login.
- Auditoria automática axe de regras WCAG A/AA nos estados testados da home, primeiro acesso e painel. Essa verificação automatizada não substitui uma avaliação completa com usuários.

## API

GET api/index.php?action=public retorna somente conteúdos publicados e informações públicas.
GET api/index.php?action=session retorna o estado de login e o token CSRF.
GET api/index.php?action=admin exige autenticação.
POST setup, login, logout, save, delete, settings e password exigem X-CSRF-Token.
Uploads usam multipart/form-data com o campo payload em JSON e o campo de imagem correspondente.

SITE_STORAGE_DIR permite usar um diretório de armazenamento diferente (usado nos testes). Não configure um diretório público para esse armazenamento.
