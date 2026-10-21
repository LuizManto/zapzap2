<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>zapzap2</title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; background: #e5ded8; }
    #app { display: flex; height: 100vh; }
    button { cursor: pointer; }

    /* ---- coluna esquerda ---- */
    #coluna-conversas { width: 340px; background: #fff; border-right: 1px solid #ddd; display: flex; flex-direction: column; }
    #cabecalho-esquerda { padding: 12px 14px; background: #f0f2f5; display: flex; justify-content: space-between; align-items: center; gap: 6px; }
    #cabecalho-esquerda .botoes { display: flex; gap: 6px; align-items: center; }
    .btn-verde { border: none; background: #25d366; color: #fff; padding: 6px 10px; border-radius: 4px; }
    .btn-cinza { border: 1px solid #ccc; background: #f5f5f5; padding: 6px 10px; border-radius: 4px; }
    .btn-vermelho { border: none; background: #e0533d; color: #fff; padding: 6px 10px; border-radius: 4px; }
    #link-sair { font-size: 12px; color: #667781; }

    .painel { display: none; padding: 10px; border-bottom: 1px solid #eee; background: #fafafa; }
    .painel input[type=text] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 6px; }
    .linha { display: flex; gap: 6px; }
    .linha input[type=text] { flex: 1; }
    .resultado div { padding: 8px; cursor: pointer; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    .resultado div:hover { background: #f0f2f5; }
    .resultado .aviso { color: #999; cursor: default; }
    .resultado .aviso:hover { background: transparent; }
    .chips { margin: 6px 0; }
    .chips span { display: inline-block; background: #d9fdd3; border-radius: 12px; padding: 3px 10px; margin: 2px; font-size: 13px; cursor: pointer; }

    #convites .convite { padding: 10px 14px; background: #fff8e1; border-bottom: 1px solid #f0e2b6; font-size: 13px; }
    #convites .botoes { margin-top: 6px; display: flex; gap: 6px; }

    #busca-conversas { padding: 8px 10px; border-bottom: 1px solid #eee; }
    #busca-conversas input { width: 100%; padding: 7px; border: 1px solid #ccc; border-radius: 4px; }

    img.avatar-lista { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    img.avatar-mini { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    img.avatar-mini-chat { width: 20px; height: 20px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    img.avatar-grande { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; }

    #rodape-perfil { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #f0f2f5; border-top: 1px solid #ddd; }
    #rodape-perfil a#meu-nome-perfil { flex: 1; font-weight: bold; font-size: 14px; color: #111; text-decoration: none; }
    #rodape-perfil a#meu-nome-perfil:hover { text-decoration: underline; }
    #rodape-perfil a#rodape-sair { font-size: 12px; color: #667781; }

    #lista-conversas { flex: 1; overflow-y: auto; }
    .item-conversa { padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; border-bottom: 1px solid #f0f0f0; gap: 10px; }
    .item-conversa:hover, .item-conversa.ativa { background: #f0f2f5; }
    .item-conversa .nome { font-weight: bold; font-size: 14px; }
    .item-conversa .ultima { font-size: 13px; color: #667781; max-width: 190px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .badge { background: #25d366; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 7px; height: fit-content; }
    .item-conversa .lado-direito { display: flex; align-items: center; gap: 6px; }
    .item-conversa .apagar { visibility: hidden; border: none; background: none; color: #999; font-size: 15px; padding: 2px 4px; }
    .item-conversa:hover .apagar { visibility: visible; }
    .item-conversa .apagar:hover { color: #b3261e; }

    /* ---- coluna direita ---- */
    #coluna-chat { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    #sem-conversa { margin: auto; color: #667781; }
    #cabecalho-chat { padding: 12px 14px; background: #f0f2f5; font-weight: bold; display: none; justify-content: space-between; align-items: center; }
    #painel-grupo { background: #fafafa; }
    #painel-grupo .acoes-grupo { display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; }
    .linha-membro { padding: 5px 0; font-size: 14px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; }
    .linha-membro.pendente { color: #999; }
    .linha-membro .acoes-membro { display: flex; gap: 6px; }
    .linha-membro .acoes-membro button { border: none; background: none; font-size: 12px; color: #667781; text-decoration: underline; padding: 0; }
    .linha-membro .acoes-membro button:hover { color: #000; }

    #carregar-mais { display: none; text-align: center; padding: 6px; }
    #carregar-mais button { border: none; background: none; color: #075e54; font-size: 13px; text-decoration: underline; }

    #mensagens { flex: 1; padding: 16px; overflow-y: auto; display: none; flex-direction: column; gap: 6px; }

    .balao { max-width: 60%; padding: 8px 12px; border-radius: 8px; font-size: 14px; word-wrap: break-word; }
    .balao.minha { align-self: flex-end; background: #d9fdd3; }
    .balao.outra { align-self: flex-start; background: #fff; }
    .balao .remetente { font-size: 12px; color: #06cf9c; font-weight: bold; }
    .balao .corpo { white-space: pre-wrap; }
    .balao.apagada .corpo { font-style: italic; color: #888; }
    .balao .rodape { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 2px; }
    .balao .hora { font-size: 10px; color: #999; margin-left: auto; }
    .balao .acoes { visibility: hidden; display: flex; gap: 8px; }
    .balao:hover .acoes { visibility: visible; }
    .balao .acoes button { border: none; background: none; padding: 0; font-size: 11px; color: #667781; text-decoration: underline; }
    .balao .acoes button:hover { color: #000; }

    /* citação de resposta dentro do balão */
    .balao .citacao {
        background: rgba(0,0,0,.05); border-left: 3px solid #06cf9c; border-radius: 4px;
        padding: 4px 8px; margin-bottom: 4px; font-size: 12px; color: #555; cursor: pointer;
    }
    .balao .citacao b { color: #06cf9c; }
    .balao .citacao.apagada { font-style: italic; color: #999; }

    /* anexos (imagem / arquivo) */
    .anexo-imagem { max-width: 240px; max-height: 240px; border-radius: 6px; display: block; cursor: pointer; }
    .anexo-arquivo { display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,.05); padding: 8px 10px; border-radius: 6px; text-decoration: none; color: #111; font-size: 13px; }
    .anexo-arquivo:hover { background: rgba(0,0,0,.1); }

    /* pré-visualização de resposta / anexo acima do campo de digitar */
    #pre-envio { display: none; padding: 8px 12px; background: #e9edf0; border-top: 1px solid #ddd; font-size: 13px; }
    #pre-envio .linha { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    #pre-envio .fechar { border: none; background: none; color: #667781; font-size: 16px; padding: 0 4px; }
    #pre-envio .fechar:hover { color: #000; }
    #pre-arquivo-nome { display: flex; align-items: center; gap: 6px; }

    #indicador-digitando { padding: 0 16px 4px; font-size: 12px; color: #667781; font-style: italic; min-height: 16px; }

    #form-envio { display: none; padding: 12px; background: #f0f2f5; gap: 8px; align-items: flex-end; }
    #input-mensagem {
        flex: 1; padding: 10px 14px; border-radius: 18px; border: 1px solid #ccc;
        resize: none; max-height: 120px; font-family: inherit; font-size: 14px; line-height: 1.3;
    }
    #form-envio button[type=submit] { border: none; background: #25d366; color: #fff; padding: 10px 18px; border-radius: 20px; }
    #btn-anexar { background: #f5f5f5; border: 1px solid #ccc; border-radius: 50%; width: 38px; height: 38px; font-size: 16px; line-height: 1; }
</style>
</head>
<body>

<div id="app">

    <div id="coluna-conversas">
        <div id="cabecalho-esquerda">
            <strong><?= esc($meuNome) ?></strong>
            <div class="botoes">
                <button class="btn-verde" id="btn-nova-conversa">+ Conversa</button>
                <button class="btn-verde" id="btn-novo-grupo">+ Grupo</button>
            </div>
        </div>

        <div id="busca-conversas">
            <input type="text" id="filtro-conversas" placeholder="Buscar nas minhas conversas...">
        </div>

        <!-- nova conversa individual -->
        <div class="painel" id="painel-nova-conversa">
            <div class="linha">
                <input type="text" id="busca-usuario" placeholder="Buscar por nome ou e-mail...">
                <button type="button" class="btn-cinza" id="btn-buscar-usuario">Buscar</button>
            </div>
            <div class="resultado" id="resultado-busca"></div>
        </div>

        <!-- novo grupo -->
        <div class="painel" id="painel-novo-grupo">
            <input type="text" id="grupo-titulo" placeholder="Nome do grupo" maxlength="150">
            <div class="linha">
                <input type="text" id="grupo-busca" placeholder="Buscar pessoas para convidar...">
                <button type="button" class="btn-cinza" id="btn-grupo-buscar">Buscar</button>
            </div>
            <div class="resultado" id="grupo-resultado"></div>
            <div class="chips" id="grupo-escolhidos"></div>
            <button type="button" class="btn-verde" id="btn-criar-grupo">Criar grupo e enviar convites</button>
        </div>

        <div id="convites"></div>
        <div id="lista-conversas"></div>

        <div id="rodape-perfil">
            <img id="meu-avatar" class="avatar-mini" alt="">
            <a id="meu-nome-perfil" href="/perfil"><?= esc($meuNome) ?></a>
            <a id="rodape-sair" href="/logout">Sair</a>
        </div>
    </div>

    <div id="coluna-chat">
        <div id="sem-conversa">Selecione uma conversa para começar</div>

        <div id="cabecalho-chat">
            <div id="cabecalho-chat-info" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <img id="avatar-chat" class="avatar-mini" alt="">
                <span id="titulo-chat"></span>
            </div>
            <button type="button" class="btn-cinza" id="btn-membros" style="display:none;">Membros</button>
        </div>

        <div class="painel" id="painel-grupo">
            <div class="acoes-grupo">
                <button type="button" class="btn-cinza" id="btn-renomear-grupo" style="display:none;">Renomear grupo</button>
                <button type="button" class="btn-vermelho" id="btn-sair-grupo">Sair do grupo</button>
            </div>
            <div id="lista-membros"></div>
            <div id="area-convidar" style="display:none; margin-top:8px;">
                <div class="linha">
                    <input type="text" id="convidar-busca" placeholder="Convidar alguém (nome ou e-mail)...">
                    <button type="button" class="btn-cinza" id="btn-convidar-buscar">Buscar</button>
                </div>
                <div class="resultado" id="convidar-resultado"></div>
            </div>
        </div>

        <div class="painel" id="painel-perfil-usuario" style="text-align:center;">
            <img id="perfil-avatar-grande" class="avatar-grande" alt="">
            <h3 id="perfil-nome-exibido" style="margin:8px 0 2px;"></h3>
            <p id="perfil-recado-exibido" style="color:#667781; margin:0 0 10px;"></p>
            <button type="button" class="btn-cinza" id="btn-fechar-perfil-usuario">Fechar</button>
        </div>

        <div id="carregar-mais"><button type="button" id="btn-carregar-mais">Carregar mensagens mais antigas</button></div>
        <div id="mensagens"></div>
        <div id="indicador-digitando"></div>

        <div id="pre-envio">
            <div class="linha" id="pre-envio-resposta" style="display:none;">
                <span>Respondendo a <b id="pre-resposta-nome"></b>: <span id="pre-resposta-texto"></span></span>
                <button type="button" class="fechar" id="btn-cancelar-resposta">✕</button>
            </div>
            <div class="linha" id="pre-envio-arquivo" style="display:none;">
                <span id="pre-arquivo-nome"></span>
                <button type="button" class="fechar" id="btn-cancelar-arquivo">✕</button>
            </div>
        </div>

        <form id="form-envio">
            <input type="file" id="input-arquivo" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" style="display:none;">
            <button type="button" class="btn-cinza" id="btn-anexar" title="Anexar imagem ou arquivo">📎</button>
            <textarea id="input-mensagem" rows="1" placeholder="Digite uma mensagem (Enter envia, Shift+Enter quebra linha)" autocomplete="off"></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>

</div>

<script>
const MEU_ID = <?= (int) $meuId ?>;
const $ = (id) => document.getElementById(id);

// monta a URL do avatar; se a pessoa não tem foto, gera um círculo colorido com a inicial do nome
function avatarSrc(nome, arquivo) {
    if (arquivo) return `/uploads/avatars/${arquivo}`;

    const letra = (nome || '?').trim().charAt(0).toUpperCase() || '?';
    const cores = ['#f08a5d', '#b83b5e', '#6a2c70', '#3f72af', '#1b998b', '#c9972c', '#e84545', '#2d4059'];
    let soma = 0;
    for (const c of (nome || '')) soma += c.charCodeAt(0);
    const cor = cores[soma % cores.length];

    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40">
        <rect width="40" height="40" rx="20" fill="${cor}"/>
        <text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#fff" font-family="Arial" font-size="18">${letra}</text>
    </svg>`;

    return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
}

let conversaAtual = null;      // { id, tipo, ehAdmin, titulo }
let ultimoIdMensagem = 0;
let primeiroIdMensagem = null; // id da mensagem mais antiga já carregada (pro "carregar mais")
let semMaisAntigas = false;
let desdeServidor = '';        // hora do servidor na última sincronização (pra achar edições/exclusões)
let intervaloPolling = null;
let conversasCache = [];       // último resultado de /chat/conversas, pra filtrar sem bater no servidor de novo
const grupoEscolhidos = new Map(); // id_usuario -> nome

let respondendoAtual = null;   // { id_mensagem, remetente_nome, corpo } ou null
let arquivoSelecionado = null; // File ou null
let ultimoEnvioDigitando = 0;  // timestamp (ms) do último aviso "estou digitando" mandado ao servidor

// ---------- util ----------

function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

// fetch que já trata erro: lança Error com a mensagem que o servidor mandou
async function api(url, opcoes = {}) {
    const resp = await fetch(url, opcoes);
    let dados = null;
    try { dados = await resp.json(); } catch (e) { /* resposta sem JSON */ }

    if (! resp.ok) {
        throw new Error((dados && dados.erro) ? dados.erro : `Erro ${resp.status}`);
    }
    return dados;
}

// POST com FormData; arrays viram campo[] (é o formato que o PHP entende como array)
function post(url, campos = {}) {
    const fd = new FormData();
    for (const [chave, valor] of Object.entries(campos)) {
        if (Array.isArray(valor)) valor.forEach(v => fd.append(chave + '[]', v));
        else fd.append(chave, valor);
    }
    return api(url, { method: 'POST', body: fd });
}

// liga um campo de busca de usuários (botão, Enter e digitação com atraso)
function ligarBusca(input, botao, container, aoEscolher) {
    async function executar() {
        const termo = input.value.trim();

        if (termo.length < 2) {
            container.innerHTML = '<div class="aviso">Digite pelo menos 2 letras.</div>';
            return;
        }

        container.innerHTML = '<div class="aviso">Buscando...</div>';

        try {
            const usuarios = await api(`/chat/usuarios?q=${encodeURIComponent(termo)}`);
            container.innerHTML = '';

            if (usuarios.length === 0) {
                container.innerHTML = '<div class="aviso">Nenhum usuário encontrado.</div>';
                return;
            }

            usuarios.forEach(u => {
                const div = document.createElement('div');
                div.textContent = `${u.nome} (${u.email})`;
                div.addEventListener('click', () => aoEscolher(u));
                container.appendChild(div);
            });
        } catch (erro) {
            container.innerHTML = `<div class="aviso" style="color:#b3261e;">${escapeHtml(erro.message)}</div>`;
        }
    }

    botao.addEventListener('click', executar);
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); executar(); } });

    let atraso = null;
    input.addEventListener('input', () => { clearTimeout(atraso); atraso = setTimeout(executar, 400); });
}

function alternarPainel(id) {
    const painel = $(id);
    painel.style.display = painel.style.display === 'block' ? 'none' : 'block';
}

// ---------- lista de conversas (esquerda) ----------

async function carregarConversas() {
    try {
        conversasCache = await api('/chat/conversas');
        desenharConversas();
        atualizarTituloComNaoLidas();
    } catch (erro) {
        console.error('Erro ao carregar conversas:', erro);
    }
}

function desenharConversas() {
    const filtro = $('filtro-conversas').value.trim().toLowerCase();
    const container = $('lista-conversas');
    container.innerHTML = '';

    conversasCache
        .filter(c => (c.nome_exibido ?? '').toLowerCase().includes(filtro))
        .forEach(c => {
            const div = document.createElement('div');
            div.className = 'item-conversa' + (conversaAtual && c.id_conversa == conversaAtual.id ? ' ativa' : '');

            const prefixo = c.tipo === 'grupo' ? '👥 ' : '';
            const nome = prefixo + (c.nome_exibido ?? '(sem nome)');
            const ultima = c.ultima_mensagem ?? 'Nenhuma mensagem ainda';
            const naoLidas = parseInt(c.nao_lidas || 0);

            div.innerHTML = `
                <img class="avatar-lista" src="${avatarSrc(c.nome_exibido, c.foto_exibida)}" alt="">
                <div style="flex:1; min-width:0;">
                    <div class="nome">${escapeHtml(nome)}</div>
                    <div class="ultima">${escapeHtml(ultima)}</div>
                </div>
                <div class="lado-direito">
                    ${naoLidas > 0 ? `<span class="badge">${naoLidas}</span>` : ''}
                    <button type="button" class="apagar" title="Apagar conversa" data-acao="apagar">✕</button>
                </div>
            `;

            div.addEventListener('click', () => abrirConversa(c.id_conversa, nome));

            div.querySelector('[data-acao=apagar]').addEventListener('click', async (e) => {
                e.stopPropagation();
                if (! confirm(`Apagar a conversa com ${c.nome_exibido}? Ela some só pra você.`)) return;

                try {
                    await post(`/chat/${c.id_conversa}/ocultar`);

                    if (conversaAtual && conversaAtual.id == c.id_conversa) {
                        fecharConversaAtual();
                    }

                    carregarConversas();
                } catch (erro) { alert(erro.message); }
            });

            container.appendChild(div);
        });
}

$('filtro-conversas').addEventListener('input', desenharConversas);

function atualizarTituloComNaoLidas() {
    const total = conversasCache.reduce((soma, c) => soma + parseInt(c.nao_lidas || 0), 0);
    document.title = total > 0 ? `(${total}) zapzap2` : 'zapzap2';
}

function fecharConversaAtual() {
    conversaAtual = null;
    if (intervaloPolling) clearInterval(intervaloPolling);

    $('sem-conversa').style.display = 'block';
    $('cabecalho-chat').style.display = 'none';
    $('mensagens').style.display = 'none';
    $('form-envio').style.display = 'none';
    $('painel-grupo').style.display = 'none';
    $('carregar-mais').style.display = 'none';
    $('indicador-digitando').textContent = '';
    limparPreEnvio();
}

// ---------- convites de grupo (esquerda) ----------

async function carregarConvites() {
    try {
        const convites = await api('/chat/convites');
        const container = $('convites');
        container.innerHTML = '';

        convites.forEach(c => {
            const div = document.createElement('div');
            div.className = 'convite';
            div.innerHTML = `
                <div><b>${escapeHtml(c.titulo)}</b></div>
                <div>${escapeHtml(c.convidado_por_nome ?? 'Alguém')} convidou você para este grupo</div>
                <div class="botoes">
                    <button type="button" class="btn-verde" data-acao="aceitar">Aceitar</button>
                    <button type="button" class="btn-vermelho" data-acao="recusar">Recusar</button>
                </div>
            `;

            div.querySelector('[data-acao=aceitar]').addEventListener('click', async () => {
                try {
                    await post(`/chat/convites/${c.id_conversa}/aceitar`);
                    await carregarConversas();
                    await carregarConvites();
                    abrirConversa(c.id_conversa, '👥 ' + c.titulo);
                } catch (erro) { alert(erro.message); }
            });

            div.querySelector('[data-acao=recusar]').addEventListener('click', async () => {
                try {
                    await post(`/chat/convites/${c.id_conversa}/recusar`);
                    carregarConvites();
                } catch (erro) { alert(erro.message); }
            });

            container.appendChild(div);
        });
    } catch (erro) {
        console.error('Erro ao carregar convites:', erro);
    }
}

// ---------- abrir conversa (direita) ----------

async function abrirConversa(idConversa, nomeExibido) {
    try {
        const dados = await api(`/chat/${idConversa}`);

        conversaAtual = {
            id: idConversa,
            tipo: dados.conversa.tipo,
            ehAdmin: dados.conversa.eh_admin,
            titulo: nomeExibido,
            outroId: dados.conversa.outro_usuario_id ?? null,
            foto: dados.conversa.foto_exibida ?? null,
        };
        desdeServidor = dados.agora;
        ultimoIdMensagem = 0;
        primeiroIdMensagem = null;
        semMaisAntigas = false;
        limparPreEnvio();
        $('indicador-digitando').textContent = '';

        $('sem-conversa').style.display = 'none';
        $('cabecalho-chat').style.display = 'flex';
        $('mensagens').style.display = 'flex';
        $('form-envio').style.display = 'flex';
        $('titulo-chat').textContent = nomeExibido;
        $('avatar-chat').src = avatarSrc(nomeExibido, conversaAtual.foto);
        $('input-mensagem').style.height = 'auto';

        // painel de membros/admin só existe em grupo
        $('painel-grupo').style.display = 'none';
        $('painel-perfil-usuario').style.display = 'none';
        $('btn-membros').style.display = conversaAtual.tipo === 'grupo' ? 'inline-block' : 'none';
        $('btn-renomear-grupo').style.display = conversaAtual.ehAdmin ? 'inline-block' : 'none';
        $('area-convidar').style.display = conversaAtual.ehAdmin ? 'block' : 'none';
        $('convidar-busca').value = '';
        $('convidar-resultado').innerHTML = '';

        const container = $('mensagens');
        container.innerHTML = '';
        dados.mensagens.forEach(upsertMensagem);

        if (dados.mensagens.length > 0) {
            primeiroIdMensagem = dados.mensagens[0].id_mensagem;
            ultimoIdMensagem = dados.mensagens[dados.mensagens.length - 1].id_mensagem;
        }

        $('carregar-mais').style.display = dados.mensagens.length >= 30 ? 'block' : 'none';

        container.scrollTop = container.scrollHeight;

        carregarConversas(); // some o badge de não lidas
        reiniciarPolling();
    } catch (erro) {
        alert(erro.message);
    }
}

// ---------- mensagens ----------

// monta o HTML do conteúdo (texto e/ou anexo) de uma mensagem não apagada
function montarCorpoMensagem(msg) {
    let html = '';

    if (msg.anexo) {
        if (msg.tipo === 'imagem') {
            html += `<img class="anexo-imagem" src="/uploads/mensagens/${encodeURIComponent(msg.anexo)}" alt="Imagem enviada" data-acao="abrir-imagem">`;
        } else {
            html += `<a class="anexo-arquivo" href="/uploads/mensagens/${encodeURIComponent(msg.anexo)}" download="${escapeHtml(msg.anexo_nome || '')}" target="_blank">
                📄 ${escapeHtml(msg.anexo_nome || 'Arquivo')}
            </a>`;
        }
    }

    if (msg.corpo) {
        html += `<div class="corpo">${escapeHtml(msg.corpo)}</div>`;
    }

    return html;
}

// resumo curto de uma mensagem, pra usar na citação ("respondendo a...") e na pré-visualização
function resumoMensagem(msg) {
    if (msg.apagada) return '🚫 Mensagem apagada';
    if (msg.tipo === 'imagem') return '📷 Imagem';
    if (msg.tipo === 'arquivo') return '📄 ' + (msg.anexo_nome || 'Arquivo');
    const texto = msg.corpo || '';
    return texto.length > 80 ? texto.slice(0, 80) + '…' : texto;
}

function criarElementoMensagem(msg) {
    const ehMinha = msg.id_remetente == MEU_ID;
    const ehGrupo = conversaAtual && conversaAtual.tipo === 'grupo';

    const div = document.createElement('div');
    div.className = 'balao ' + (ehMinha ? 'minha' : 'outra') + (msg.apagada ? ' apagada' : '');
    div.dataset.id = msg.id_mensagem;

    const hora = new Date(msg.criado_em.replace(' ', 'T')).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    const corpoHtml = msg.apagada ? '<div class="corpo">🚫 Mensagem apagada</div>' : montarCorpoMensagem(msg);

    const podeApagar  = ehMinha && ! msg.apagada;
    const podeEditar  = ehMinha && ! msg.apagada && msg.tipo === 'texto';
    const podeResponder = ! msg.apagada;

    const citacaoHtml = msg.resposta_a ? `
        <div class="citacao${msg.resposta_a.apagada ? ' apagada' : ''}" data-acao="ver-citacao">
            <b>${escapeHtml(msg.resposta_a.remetente_nome || '')}</b><br>
            ${escapeHtml(resumoMensagem(msg.resposta_a))}
        </div>
    ` : '';

    div.innerHTML = `
        ${(! ehMinha && ehGrupo) ? `<div class="remetente-linha" style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
            <img class="avatar-mini-chat" src="${avatarSrc(msg.remetente_nome, msg.remetente_avatar)}" alt="">
            <span class="remetente" style="cursor:pointer;" data-acao="ver-perfil">${escapeHtml(msg.remetente_nome)}</span>
        </div>` : ''}
        ${citacaoHtml}
        ${corpoHtml}
        <div class="rodape">
            <span class="acoes">
                ${podeResponder ? `<button type="button" data-acao="responder">Responder</button>` : ''}
                ${podeEditar ? `<button type="button" data-acao="editar">Editar</button>` : ''}
                ${podeApagar ? `<button type="button" data-acao="apagar">Apagar</button>` : ''}
            </span>
            <span class="hora">${msg.editada && ! msg.apagada ? '(editada) ' : ''}${hora}</span>
        </div>
    `;

    const btnResponder = div.querySelector('[data-acao=responder]');
    if (btnResponder) btnResponder.addEventListener('click', () => iniciarResposta(msg));

    const btnEditar = div.querySelector('[data-acao=editar]');
    if (btnEditar) btnEditar.addEventListener('click', () => editarMensagem(msg));

    const btnApagar = div.querySelector('[data-acao=apagar]');
    if (btnApagar) btnApagar.addEventListener('click', () => apagarMensagem(msg));

    const imagemAnexo = div.querySelector('[data-acao=abrir-imagem]');
    if (imagemAnexo) imagemAnexo.addEventListener('click', () => window.open(imagemAnexo.src, '_blank'));

    const nomeClicavel = div.querySelector('[data-acao=ver-perfil]');
    if (nomeClicavel) nomeClicavel.addEventListener('click', () => abrirPerfilUsuario(msg.id_remetente));

    const citacao = div.querySelector('[data-acao=ver-citacao]');
    if (citacao && msg.resposta_a) {
        citacao.addEventListener('click', () => {
            const alvo = $('mensagens').querySelector(`[data-id="${msg.resposta_a.id_mensagem}"]`);
            if (alvo) alvo.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    return div;
}

// cria a mensagem na tela, ou substitui se ela já estiver lá (edição/exclusão)
function upsertMensagem(msg) {
    const container = $('mensagens');
    const novo = criarElementoMensagem(msg);
    const existente = container.querySelector(`[data-id="${msg.id_mensagem}"]`);

    if (existente) existente.replaceWith(novo);
    else container.appendChild(novo);
}

$('btn-carregar-mais').addEventListener('click', carregarMensagensAntigas);

async function carregarMensagensAntigas() {
    if (! conversaAtual || primeiroIdMensagem === null || semMaisAntigas) return;

    const botao = $('btn-carregar-mais');
    const textoOriginal = botao.textContent;
    botao.textContent = 'Carregando...';
    botao.disabled = true;

    try {
        const antigas = await api(`/chat/${conversaAtual.id}/antigas/${primeiroIdMensagem}`);

        if (antigas.length === 0) {
            semMaisAntigas = true;
            $('carregar-mais').style.display = 'none';
            return;
        }

        const container = $('mensagens');
        const alturaAntes = container.scrollHeight;

        // insere no topo, mantendo a ordem cronológica
        antigas.slice().reverse().forEach(msg => {
            container.insertBefore(criarElementoMensagem(msg), container.firstChild);
        });

        primeiroIdMensagem = antigas[0].id_mensagem;

        // mantém a posição de leitura (não deixa "pular" a tela quando insere em cima)
        container.scrollTop = container.scrollHeight - alturaAntes;

        if (antigas.length < 30) {
            semMaisAntigas = true;
            $('carregar-mais').style.display = 'none';
        }
    } catch (erro) {
        alert(erro.message);
    } finally {
        botao.textContent = textoOriginal;
        botao.disabled = false;
    }
}

async function editarMensagem(msg) {
    const novoTexto = prompt('Editar mensagem:', msg.corpo);

    if (novoTexto === null) return;                    // cancelou
    if (novoTexto.trim() === '') { alert('A mensagem não pode ficar vazia.'); return; }
    if (novoTexto.trim() === msg.corpo) return;        // não mudou nada

    try {
        await post(`/chat/mensagem/${msg.id_mensagem}/editar`, { corpo: novoTexto });
        buscarMensagensNovas();
    } catch (erro) { alert(erro.message); }
}

async function apagarMensagem(msg) {
    if (! confirm('Apagar esta mensagem para todos?')) return;

    try {
        await post(`/chat/mensagem/${msg.id_mensagem}/apagar`);
        buscarMensagensNovas();
    } catch (erro) { alert(erro.message); }
}

// ---------- responder / anexar (pré-visualização acima do campo de digitar) ----------

function atualizarVisibilidadePreEnvio() {
    const temAlgo = respondendoAtual !== null || arquivoSelecionado !== null;
    $('pre-envio').style.display = temAlgo ? 'block' : 'none';
}

function iniciarResposta(msg) {
    respondendoAtual = { id_mensagem: msg.id_mensagem, remetente_nome: msg.remetente_nome, corpo: resumoMensagem(msg) };

    $('pre-resposta-nome').textContent = msg.id_remetente == MEU_ID ? 'você' : msg.remetente_nome;
    $('pre-resposta-texto').textContent = respondendoAtual.corpo;
    $('pre-envio-resposta').style.display = 'flex';
    atualizarVisibilidadePreEnvio();

    $('input-mensagem').focus();
}

$('btn-cancelar-resposta').addEventListener('click', () => {
    respondendoAtual = null;
    $('pre-envio-resposta').style.display = 'none';
    atualizarVisibilidadePreEnvio();
});

$('btn-anexar').addEventListener('click', () => $('input-arquivo').click());

$('input-arquivo').addEventListener('change', () => {
    const arquivo = $('input-arquivo').files[0];
    if (! arquivo) return;

    if (arquivo.size > 10 * 1024 * 1024) {
        alert('Arquivo muito grande (máximo 10MB).');
        $('input-arquivo').value = '';
        return;
    }

    arquivoSelecionado = arquivo;
    $('pre-arquivo-nome').textContent = '📎 ' + arquivo.name;
    $('pre-envio-arquivo').style.display = 'flex';
    atualizarVisibilidadePreEnvio();
});

$('btn-cancelar-arquivo').addEventListener('click', () => {
    arquivoSelecionado = null;
    $('input-arquivo').value = '';
    $('pre-envio-arquivo').style.display = 'none';
    atualizarVisibilidadePreEnvio();
});

function limparPreEnvio() {
    respondendoAtual = null;
    arquivoSelecionado = null;
    $('input-arquivo').value = '';
    $('pre-envio-resposta').style.display = 'none';
    $('pre-envio-arquivo').style.display = 'none';
    $('pre-envio').style.display = 'none';
}

$('form-envio').addEventListener('submit', async (e) => {
    e.preventDefault();

    const input = $('input-mensagem');
    const corpo = input.value.trim();
    if (! conversaAtual) return;
    if (! corpo && ! arquivoSelecionado) return; // nada pra enviar

    const campos = { corpo };
    if (respondendoAtual) campos.respondendo_a = respondendoAtual.id_mensagem;
    if (arquivoSelecionado) campos.arquivo = arquivoSelecionado;

    input.value = '';
    input.style.height = 'auto';
    limparPreEnvio();

    try {
        await post(`/chat/${conversaAtual.id}/enviar`, campos);
        buscarMensagensNovas();
    } catch (erro) {
        input.value = corpo; // devolve o texto pro campo pra não perder
        alert(erro.message);
    }
});

// Enter envia; Shift+Enter quebra linha
$('input-mensagem').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && ! e.shiftKey) {
        e.preventDefault();
        $('form-envio').requestSubmit();
    }
});

// cresce junto com o texto (até o max-height do CSS) e avisa o servidor que estou digitando
$('input-mensagem').addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';

    if (! conversaAtual || this.value.trim() === '') return;

    const agora = Date.now();
    if (agora - ultimoEnvioDigitando > 2000) { // no máximo 1 aviso a cada 2s
        ultimoEnvioDigitando = agora;
        post(`/chat/${conversaAtual.id}/digitando`).catch(() => {}); // falha aqui não precisa incomodar o usuário
    }
});

// ---------- polling ----------

function reiniciarPolling() {
    if (intervaloPolling) clearInterval(intervaloPolling);
    intervaloPolling = setInterval(buscarMensagensNovas, 3000);
}

async function buscarMensagensNovas() {
    if (! conversaAtual) return;
    const idConversa = conversaAtual.id;

    try {
        const dados = await api(`/chat/${idConversa}/novas/${ultimoIdMensagem}?desde=${encodeURIComponent(desdeServidor)}`);

        if (! conversaAtual || conversaAtual.id !== idConversa) return; // trocou de conversa no meio da requisição

        desdeServidor = dados.agora;

        dados.alteradas.forEach(upsertMensagem); // editadas / apagadas

        if (dados.novas.length > 0) {
            dados.novas.forEach(upsertMensagem);
            ultimoIdMensagem = Math.max(ultimoIdMensagem, dados.novas[dados.novas.length - 1].id_mensagem);

            const container = $('mensagens');
            container.scrollTop = container.scrollHeight;
        }

        if (dados.novas.length > 0 || dados.alteradas.length > 0) {
            carregarConversas();
        }

        atualizarIndicadorDigitando(dados.digitando || []);
    } catch (erro) {
        console.error('Erro no polling:', erro);
    }
}

function atualizarIndicadorDigitando(nomes) {
    if (! nomes.length) {
        $('indicador-digitando').textContent = '';
    } else if (nomes.length === 1) {
        $('indicador-digitando').textContent = `${nomes[0]} está digitando...`;
    } else {
        $('indicador-digitando').textContent = `${nomes.join(', ')} estão digitando...`;
    }
}

// atualiza lista de conversas e convites mesmo sem nenhuma conversa aberta
setInterval(() => { carregarConversas(); carregarConvites(); }, 5000);

// ---------- nova conversa individual ----------

$('btn-nova-conversa').addEventListener('click', () => {
    $('painel-novo-grupo').style.display = 'none';
    alternarPainel('painel-nova-conversa');
});

ligarBusca($('busca-usuario'), $('btn-buscar-usuario'), $('resultado-busca'), async (u) => {
    try {
        const dados = await post('/chat/iniciar', { id_usuario: u.id_usuario });

        $('painel-nova-conversa').style.display = 'none';
        $('busca-usuario').value = '';
        $('resultado-busca').innerHTML = '';

        await carregarConversas();
        abrirConversa(dados.id_conversa, u.nome);
    } catch (erro) { alert(erro.message); }
});

// ---------- novo grupo ----------

function desenharEscolhidos() {
    const container = $('grupo-escolhidos');
    container.innerHTML = '';

    grupoEscolhidos.forEach((nome, id) => {
        const chip = document.createElement('span');
        chip.textContent = nome + ' ✕';
        chip.title = 'Clique para remover';
        chip.addEventListener('click', () => { grupoEscolhidos.delete(id); desenharEscolhidos(); });
        container.appendChild(chip);
    });
}

$('btn-novo-grupo').addEventListener('click', () => {
    $('painel-nova-conversa').style.display = 'none';
    alternarPainel('painel-novo-grupo');
});

ligarBusca($('grupo-busca'), $('btn-grupo-buscar'), $('grupo-resultado'), (u) => {
    grupoEscolhidos.set(u.id_usuario, u.nome);
    desenharEscolhidos();
});

$('btn-criar-grupo').addEventListener('click', async () => {
    const titulo = $('grupo-titulo').value.trim();

    if (! titulo) { alert('Dê um nome ao grupo.'); return; }
    if (grupoEscolhidos.size === 0) { alert('Escolha pelo menos uma pessoa para convidar.'); return; }

    try {
        const dados = await post('/chat/grupo', { titulo, membros: [...grupoEscolhidos.keys()] });

        $('painel-novo-grupo').style.display = 'none';
        $('grupo-titulo').value = '';
        $('grupo-busca').value = '';
        $('grupo-resultado').innerHTML = '';
        grupoEscolhidos.clear();
        desenharEscolhidos();

        await carregarConversas();
        abrirConversa(dados.id_conversa, '👥 ' + titulo);
    } catch (erro) { alert(erro.message); }
});

// ---------- membros / convidar (dentro do grupo aberto) ----------

async function carregarMembros() {
    if (! conversaAtual) return;

    try {
        const membros = await api(`/chat/${conversaAtual.id}/membros`);
        const container = $('lista-membros');
        container.innerHTML = '';

        membros.forEach(m => {
            const div = document.createElement('div');
            const pendente = m.status === 'pendente';
            div.className = 'linha-membro' + (pendente ? ' pendente' : '');

            const podeGerenciar = conversaAtual.ehAdmin && ! pendente && m.id_usuario != MEU_ID && ! m.eh_admin;

            div.innerHTML = `
                <span style="display:flex; align-items:center; gap:8px; ${pendente ? '' : 'cursor:pointer;'}" data-acao="${pendente ? '' : 'ver-perfil'}">
                    <img class="avatar-mini" src="${avatarSrc(m.nome, m.avatar)}" alt="">
                    ${escapeHtml(m.nome)}${m.eh_admin ? ' ⭐ admin' : ''}${pendente ? ' (convite pendente)' : ''}
                </span>
                ${podeGerenciar ? `<span class="acoes-membro">
                    <button type="button" data-acao="promover">Tornar admin</button>
                    <button type="button" data-acao="remover">Remover</button>
                </span>` : ''}
            `;

            if (! pendente) {
                div.querySelector('[data-acao=ver-perfil]').addEventListener('click', () => abrirPerfilUsuario(m.id_usuario));
            }

            if (podeGerenciar) {
                div.querySelector('[data-acao=promover]').addEventListener('click', async () => {
                    try {
                        await post(`/chat/${conversaAtual.id}/membros/${m.id_usuario}/promover`);
                        carregarMembros();
                    } catch (erro) { alert(erro.message); }
                });

                div.querySelector('[data-acao=remover]').addEventListener('click', async () => {
                    if (! confirm(`Remover ${m.nome} do grupo?`)) return;
                    try {
                        await post(`/chat/${conversaAtual.id}/membros/${m.id_usuario}/remover`);
                        carregarMembros();
                    } catch (erro) { alert(erro.message); }
                });
            }

            container.appendChild(div);
        });
    } catch (erro) { alert(erro.message); }
}

$('btn-membros').addEventListener('click', () => {
    alternarPainel('painel-grupo');
    if ($('painel-grupo').style.display === 'block') carregarMembros();
});

$('cabecalho-chat-info').addEventListener('click', () => {
    if (! conversaAtual) return;

    if (conversaAtual.tipo === 'individual' && conversaAtual.outroId) {
        abrirPerfilUsuario(conversaAtual.outroId);
    } else if (conversaAtual.tipo === 'grupo') {
        alternarPainel('painel-grupo');
        if ($('painel-grupo').style.display === 'block') carregarMembros();
    }
});

async function abrirPerfilUsuario(idUsuario) {
    try {
        const perfil = await api(`/chat/usuario/${idUsuario}/perfil`);

        $('painel-grupo').style.display = 'none';
        $('perfil-avatar-grande').src = avatarSrc(perfil.nome, perfil.avatar);
        $('perfil-nome-exibido').textContent = perfil.nome;
        $('perfil-recado-exibido').textContent = perfil.status || '';
        $('painel-perfil-usuario').style.display = 'block';
    } catch (erro) {
        alert(erro.message);
    }
}

$('btn-fechar-perfil-usuario').addEventListener('click', () => {
    $('painel-perfil-usuario').style.display = 'none';
});

$('btn-sair-grupo').addEventListener('click', async () => {
    if (! conversaAtual) return;
    if (! confirm('Tem certeza que quer sair deste grupo?')) return;

    try {
        await post(`/chat/${conversaAtual.id}/sair`);
        fecharConversaAtual();
        carregarConversas();
    } catch (erro) { alert(erro.message); }
});

$('btn-renomear-grupo').addEventListener('click', async () => {
    if (! conversaAtual) return;

    const novoNome = prompt('Novo nome do grupo:', conversaAtual.titulo.replace('👥 ', ''));
    if (! novoNome || ! novoNome.trim()) return;

    try {
        await post(`/chat/${conversaAtual.id}/renomear`, { titulo: novoNome.trim() });

        conversaAtual.titulo = '👥 ' + novoNome.trim();
        $('titulo-chat').textContent = conversaAtual.titulo;
        carregarConversas();
    } catch (erro) { alert(erro.message); }
});

ligarBusca($('convidar-busca'), $('btn-convidar-buscar'), $('convidar-resultado'), async (u) => {
    if (! conversaAtual) return;

    try {
        await post(`/chat/${conversaAtual.id}/convidar`, { membros: [u.id_usuario] });
        alert(`Convite enviado para ${u.nome}. Ele(a) precisa aceitar para entrar no grupo.`);

        $('convidar-busca').value = '';
        $('convidar-resultado').innerHTML = '';
        carregarMembros();
    } catch (erro) { alert(erro.message); }
});

// ---------- inicialização ----------

$('meu-avatar').src = avatarSrc('<?= esc($meuNome, "js") ?>', <?= $meuAvatar ? "'" . esc($meuAvatar, 'js') . "'" : 'null' ?>);

carregarConversas();
carregarConvites();
</script>

</body>
</html>
