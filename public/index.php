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

    #lista-conversas { flex: 1; overflow-y: auto; }
    .item-conversa { padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
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

    #form-envio { display: none; padding: 12px; background: #f0f2f5; gap: 8px; align-items: flex-end; }
    #input-mensagem {
        flex: 1; padding: 10px 14px; border-radius: 18px; border: 1px solid #ccc;
        resize: none; max-height: 120px; font-family: inherit; font-size: 14px; line-height: 1.3;
    }
    #form-envio button { border: none; background: #25d366; color: #fff; padding: 10px 18px; border-radius: 20px; }
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
                <a id="link-perfil" href="/perfil">Perfil</a>
                <a id="link-sair" href="/logout">Sair</a>
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
    </div>

    <div id="coluna-chat">
        <div id="sem-conversa">Selecione uma conversa para começar</div>

        <div id="cabecalho-chat">
            <span id="titulo-chat"></span>
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

        <div id="carregar-mais"><button type="button" id="btn-carregar-mais">Carregar mensagens mais antigas</button></div>
        <div id="mensagens"></div>

        <form id="form-envio">
            <textarea id="input-mensagem" rows="1" placeholder="Digite uma mensagem (Enter envia, Shift+Enter quebra linha)" autocomplete="off"></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>

</div>

<script>
const MEU_ID = <?= (int) $meuId ?>;
const $ = (id) => document.getElementById(id);

let conversaAtual = null;      // { id, tipo, ehAdmin, titulo }
let ultimoIdMensagem = 0;
let primeiroIdMensagem = null; // id da mensagem mais antiga já carregada (pro "carregar mais")
let semMaisAntigas = false;
let desdeServidor = '';        // hora do servidor na última sincronização (pra achar edições/exclusões)
let intervaloPolling = null;
let conversasCache = [];       // último resultado de /chat/conversas, pra filtrar sem bater no servidor de novo
const grupoEscolhidos = new Map(); // id_usuario -> nome

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
                <div>
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
        };
        desdeServidor = dados.agora;
        ultimoIdMensagem = 0;
        primeiroIdMensagem = null;
        semMaisAntigas = false;

        $('sem-conversa').style.display = 'none';
        $('cabecalho-chat').style.display = 'flex';
        $('mensagens').style.display = 'flex';
        $('form-envio').style.display = 'flex';
        $('titulo-chat').textContent = nomeExibido;
        $('input-mensagem').style.height = 'auto';

        // painel de membros/admin só existe em grupo
        $('painel-grupo').style.display = 'none';
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

function criarElementoMensagem(msg) {
    const ehMinha = msg.id_remetente == MEU_ID;
    const ehGrupo = conversaAtual && conversaAtual.tipo === 'grupo';

    const div = document.createElement('div');
    div.className = 'balao ' + (ehMinha ? 'minha' : 'outra') + (msg.apagada ? ' apagada' : '');
    div.dataset.id = msg.id_mensagem;

    const hora = new Date(msg.criado_em.replace(' ', 'T')).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    const corpo = msg.apagada ? '🚫 Mensagem apagada' : escapeHtml(msg.corpo);
    const podeAgir = ehMinha && ! msg.apagada && msg.tipo === 'texto';

    div.innerHTML = `
        ${(! ehMinha && ehGrupo) ? `<div class="remetente">${escapeHtml(msg.remetente_nome)}</div>` : ''}
        <div class="corpo">${corpo}</div>
        <div class="rodape">
            ${podeAgir ? `<span class="acoes">
                <button type="button" data-acao="editar">Editar</button>
                <button type="button" data-acao="apagar">Apagar</button>
            </span>` : ''}
            <span class="hora">${msg.editada && ! msg.apagada ? '(editada) ' : ''}${hora}</span>
        </div>
    `;

    if (podeAgir) {
        div.querySelector('[data-acao=editar]').addEventListener('click', () => editarMensagem(msg));
        div.querySelector('[data-acao=apagar]').addEventListener('click', () => apagarMensagem(msg));
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

$('form-envio').addEventListener('submit', async (e) => {
    e.preventDefault();

    const input = $('input-mensagem');
    const corpo = input.value.trim();
    if (! corpo || ! conversaAtual) return;

    input.value = '';
    input.style.height = 'auto';

    try {
        await post(`/chat/${conversaAtual.id}/enviar`, { corpo });
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

// cresce junto com o texto, até uma altura máxima (o CSS trava o max-height)
$('input-mensagem').addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
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
    } catch (erro) {
        console.error('Erro no polling:', erro);
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
                <span>${escapeHtml(m.nome)}${m.eh_admin ? ' ⭐ admin' : ''}${pendente ? ' (convite pendente)' : ''}</span>
                ${podeGerenciar ? `<span class="acoes-membro">
                    <button type="button" data-acao="promover">Tornar admin</button>
                    <button type="button" data-acao="remover">Remover</button>
                </span>` : ''}
            `;

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

carregarConversas();
carregarConvites();
</script>

</body>
</html>
