<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>zapzap2</title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; background: #e5ded8; }
    #app { display: flex; height: 100vh; }

    /* ---- coluna esquerda ---- */
    #coluna-conversas {
        width: 320px;
        background: #fff;
        border-right: 1px solid #ddd;
        display: flex;
        flex-direction: column;
    }
    #cabecalho-esquerda {
        padding: 14px; background: #f0f2f5; display: flex; justify-content: space-between; align-items: center;
    }
    #cabecalho-esquerda button { border: none; background: #25d366; color: #fff; padding: 6px 10px; border-radius: 4px; cursor: pointer; }
    #busca-nova-conversa { padding: 10px; display: none; border-bottom: 1px solid #eee; }
    #busca-nova-conversa input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    #resultado-busca div { padding: 8px; cursor: pointer; border-bottom: 1px solid #f5f5f5; }
    #resultado-busca div:hover { background: #f0f2f5; }

    #lista-conversas { flex: 1; overflow-y: auto; }
    .item-conversa { padding: 12px 14px; display: flex; justify-content: space-between; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
    .item-conversa:hover, .item-conversa.ativa { background: #f0f2f5; }
    .item-conversa .nome { font-weight: bold; font-size: 14px; }
    .item-conversa .ultima { font-size: 13px; color: #667781; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .badge { background: #25d366; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 7px; height: fit-content; }

    /* ---- coluna direita ---- */
    #coluna-chat { flex: 1; display: flex; flex-direction: column; }
    #sem-conversa { margin: auto; color: #667781; }
    #cabecalho-chat { padding: 14px; background: #f0f2f5; font-weight: bold; display: none; }
    #mensagens { flex: 1; padding: 16px; overflow-y: auto; display: none; flex-direction: column; gap: 6px; }
    .balao { max-width: 60%; padding: 8px 12px; border-radius: 8px; font-size: 14px; }
    .balao.minha { align-self: flex-end; background: #d9fdd3; }
    .balao.outra { align-self: flex-start; background: #fff; }
    .balao .remetente { font-size: 12px; color: #06cf9c; font-weight: bold; }
    .balao .hora { font-size: 10px; color: #999; text-align: right; margin-top: 2px; }

    #form-envio { display: none; padding: 12px; background: #f0f2f5; gap: 8px; }
    #form-envio input { flex: 1; padding: 10px; border-radius: 20px; border: 1px solid #ccc; }
    #form-envio button { border: none; background: #25d366; color: #fff; padding: 0 18px; border-radius: 20px; cursor: pointer; }
</style>
</head>
<body>

<div id="app">

    <div id="coluna-conversas">
        <div id="cabecalho-esquerda">
            <strong><?= esc($meuNome) ?></strong>
            <button id="btn-nova-conversa">+ Nova conversa</button>
        </div>

        <div id="busca-nova-conversa">
            <div style="display:flex; gap:6px;">
                <input type="text" id="input-busca-usuario" placeholder="Buscar por nome ou e-mail...">
                <button type="button" id="btn-buscar-usuario">Buscar</button>
            </div>
            <div id="resultado-busca"></div>
        </div>

        <div id="lista-conversas"></div>
    </div>

    <div id="coluna-chat">
        <div id="sem-conversa">Selecione uma conversa para começar</div>
        <div id="cabecalho-chat"></div>
        <div id="mensagens"></div>
        <form id="form-envio">
            <input type="text" id="input-mensagem" placeholder="Digite uma mensagem" autocomplete="off">
            <button type="submit">Enviar</button>
        </form>
    </div>

</div>

<script>
const MEU_ID = <?= (int) $meuId ?>;

let conversaAtualId = null;
let ultimoIdMensagem = 0;
let intervaloPolling = null;

// ---------- lista de conversas (coluna esquerda) ----------

async function carregarConversas() {
    const resp = await fetch('/chat/conversas');
    const conversas = await resp.json();

    const container = document.getElementById('lista-conversas');
    container.innerHTML = '';

    conversas.forEach(c => {
        const div = document.createElement('div');
        div.className = 'item-conversa' + (c.id_conversa == conversaAtualId ? ' ativa' : '');
        div.dataset.id = c.id_conversa;

        const nome = c.nome_exibido ?? '(sem nome)';
        const ultima = c.ultima_mensagem ?? 'Nenhuma mensagem ainda';
        const naoLidas = parseInt(c.nao_lidas || 0);

        div.innerHTML = `
            <div>
                <div class="nome">${escapeHtml(nome)}</div>
                <div class="ultima">${escapeHtml(ultima)}</div>
            </div>
            ${naoLidas > 0 ? `<span class="badge">${naoLidas}</span>` : ''}
        `;

        div.addEventListener('click', () => abrirConversa(c.id_conversa, nome));
        container.appendChild(div);
    });
}

// ---------- abrir uma conversa (coluna direita) ----------

async function abrirConversa(idConversa, nomeExibido) {
    conversaAtualId = idConversa;
    ultimoIdMensagem = 0;

    document.getElementById('sem-conversa').style.display = 'none';
    document.getElementById('cabecalho-chat').style.display = 'block';
    document.getElementById('mensagens').style.display = 'flex';
    document.getElementById('form-envio').style.display = 'flex';
    document.getElementById('cabecalho-chat').textContent = nomeExibido;

    const resp = await fetch(`/chat/${idConversa}`);

    if (! resp.ok) {
        console.error('Erro ao abrir conversa:', resp.status, await resp.text());
        alert(`Erro ${resp.status} ao abrir a conversa. Veja o Console (F12) para detalhes.`);
        return;
    }

    const dados = await resp.json();

    const container = document.getElementById('mensagens');
    container.innerHTML = '';
    dados.mensagens.forEach(desenharMensagem);

    if (dados.mensagens.length > 0) {
        ultimoIdMensagem = dados.mensagens[dados.mensagens.length - 1].id_mensagem;
    }

    container.scrollTop = container.scrollHeight;

    // atualiza a listinha da esquerda (some o badge de não lidas dessa conversa)
    carregarConversas();

    reiniciarPolling();
}

function desenharMensagem(msg) {
    const container = document.getElementById('mensagens');
    const ehMinha = msg.id_remetente == MEU_ID;

    const div = document.createElement('div');
    div.className = 'balao ' + (ehMinha ? 'minha' : 'outra');

    const hora = new Date(msg.criado_em).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    div.innerHTML = `
        ${! ehMinha ? `<div class="remetente">${escapeHtml(msg.remetente_nome)}</div>` : ''}
        <div class="corpo">${escapeHtml(msg.corpo)}</div>
        <div class="hora">${hora}</div>
    `;

    container.appendChild(div);
}

// ---------- enviar mensagem ----------

document.getElementById('form-envio').addEventListener('submit', async (e) => {
    e.preventDefault();

    const input = document.getElementById('input-mensagem');
    const corpo = input.value.trim();
    if (! corpo || ! conversaAtualId) return;

    input.value = '';

    const formData = new FormData();
    formData.append('corpo', corpo);

    const resp = await fetch(`/chat/${conversaAtualId}/enviar`, { method: 'POST', body: formData });

    if (! resp.ok) {
        console.error('Erro ao enviar mensagem:', resp.status, await resp.text());
        alert(`Erro ${resp.status} ao enviar mensagem. Veja o Console (F12) para detalhes.`);
        return;
    }

    // busca imediatamente as mensagens novas (inclusive a que acabamos de mandar)
    buscarMensagensNovas();
});

// ---------- polling ----------

function reiniciarPolling() {
    if (intervaloPolling) clearInterval(intervaloPolling);
    intervaloPolling = setInterval(buscarMensagensNovas, 3000);
}

async function buscarMensagensNovas() {
    if (! conversaAtualId) return;

    const resp = await fetch(`/chat/${conversaAtualId}/novas/${ultimoIdMensagem}`);
    const novas = await resp.json();

    if (novas.length > 0) {
        novas.forEach(desenharMensagem);
        ultimoIdMensagem = novas[novas.length - 1].id_mensagem;

        const container = document.getElementById('mensagens');
        container.scrollTop = container.scrollHeight;

        carregarConversas(); // atualiza última mensagem/ordem na coluna esquerda
    }
}

// atualiza a lista de conversas (última mensagem, badges) mesmo sem ter nenhuma aberta
setInterval(carregarConversas, 5000);

// ---------- nova conversa ----------

document.getElementById('btn-nova-conversa').addEventListener('click', () => {
    const painel = document.getElementById('busca-nova-conversa');
    painel.style.display = painel.style.display === 'block' ? 'none' : 'block';
});

async function executarBuscaUsuario() {
    const termo = document.getElementById('input-busca-usuario').value;
    const container = document.getElementById('resultado-busca');

    if (termo.length < 2) {
        container.innerHTML = '<div style="color:#999;padding:8px;">Digite pelo menos 2 letras.</div>';
        return;
    }

    container.innerHTML = '<div style="color:#999;padding:8px;">Buscando...</div>';

    try {
        const resp = await fetch(`/chat/usuarios?q=${encodeURIComponent(termo)}`);

        if (! resp.ok) {
            container.innerHTML = `<div style="color:#b3261e;padding:8px;">Erro ${resp.status} ao buscar. Confira se a rota /chat/usuarios foi registrada.</div>`;
            console.error('Erro na busca de usuários:', resp.status, await resp.text());
            return;
        }

        const usuarios = await resp.json();
        container.innerHTML = '';

        if (usuarios.length === 0) {
            container.innerHTML = '<div style="color:#999;padding:8px;">Nenhum usuário encontrado (lembre: você mesmo não aparece na busca).</div>';
            return;
        }

        usuarios.forEach(u => {
            const div = document.createElement('div');
            div.textContent = `${u.nome} (${u.email})`;
            div.addEventListener('click', () => iniciarConversa(u.id_usuario, u.nome));
            container.appendChild(div);
        });
    } catch (erro) {
        container.innerHTML = '<div style="color:#b3261e;padding:8px;">Falha de conexão ao buscar.</div>';
        console.error('Falha de conexão na busca de usuários:', erro);
    }
}

document.getElementById('btn-buscar-usuario').addEventListener('click', executarBuscaUsuario);

document.getElementById('input-busca-usuario').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        executarBuscaUsuario();
    }
});

let debounceBusca = null;
document.getElementById('input-busca-usuario').addEventListener('input', () => {
    clearTimeout(debounceBusca);
    debounceBusca = setTimeout(executarBuscaUsuario, 400);
});

async function iniciarConversa(idUsuario, nome) {
    try {
        const formData = new FormData();
        formData.append('id_usuario', idUsuario);

        const resp = await fetch('/chat/iniciar', { method: 'POST', body: formData });

        if (! resp.ok) {
            const corpoErro = await resp.text();
            console.error('Erro ao iniciar conversa:', resp.status, corpoErro);
            alert(`Erro ${resp.status} ao iniciar conversa. Veja o Console (F12) para detalhes.`);
            return;
        }

        const dados = await resp.json();

        document.getElementById('busca-nova-conversa').style.display = 'none';
        document.getElementById('input-busca-usuario').value = '';
        document.getElementById('resultado-busca').innerHTML = '';

        await carregarConversas();
        abrirConversa(dados.id_conversa, nome);
    } catch (erro) {
        console.error('Falha de conexão ao iniciar conversa:', erro);
        alert('Falha de conexão ao iniciar conversa. Veja o Console (F12) para detalhes.');
    }
}

// ---------- util ----------

function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

// ---------- inicialização ----------

carregarConversas();
</script>

</body>
</html>
