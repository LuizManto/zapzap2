<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu perfil - zapzap2</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .caixa { background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.1); width: 360px; }
        h1 { font-size: 20px; margin-bottom: 20px; }
        label { display: block; margin-bottom: 4px; font-size: 14px; color: #333; }
        input[type=text], input[type=password] { width: 100%; padding: 10px; margin-bottom: 14px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input[type=file] { margin-bottom: 14px; }
        button { width: 100%; padding: 10px; background: #25d366; color: #fff; border: none; border-radius: 4px; font-size: 15px; cursor: pointer; }
        .erro { background: #fdecea; color: #b3261e; padding: 8px; border-radius: 4px; margin-bottom: 14px; font-size: 13px; }
        .erro ul { margin: 0; padding-left: 18px; }
        .sucesso { background: #e6f6ea; color: #1e7b34; padding: 8px; border-radius: 4px; margin-bottom: 14px; font-size: 14px; }
        .avatar-atual { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; display: block; margin-bottom: 10px; }
        p { text-align: center; font-size: 14px; }
        .dica { color: #667781; font-size: 12px; margin-top: -10px; margin-bottom: 14px; }
    </style>
</head>
<body>
    <div class="caixa">
        <h1>Meu perfil</h1>

        <?php if (session()->getFlashdata('sucesso')): ?>
            <div class="sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
        <?php endif; ?>

        <?php if (! empty($errors)): ?>
            <div class="erro">
                <ul>
                    <?php foreach ($errors as $mensagem): ?>
                        <li><?= esc($mensagem) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (! empty($usuario['avatar'])): ?>
            <img class="avatar-atual" src="/uploads/avatars/<?= esc($usuario['avatar']) ?>" alt="Foto de perfil atual">
        <?php endif; ?>

        <form method="post" action="/perfil" enctype="multipart/form-data">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= esc($usuario['nome'] ?? '') ?>" required>

            <label for="status">Recado</label>
            <input type="text" id="status" name="status" value="<?= esc($usuario['status'] ?? '') ?>" maxlength="300">

            <label for="avatar">Foto de perfil</label>
            <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/gif">
            <div class="dica">JPG, PNG ou GIF, até 2MB. Deixe em branco para manter a foto atual.</div>

            <label for="senha">Nova senha</label>
            <input type="password" id="senha" name="senha" minlength="6" placeholder="Deixe em branco para não alterar">

            <button type="submit">Salvar</button>
        </form>

        <p><a href="/chat">Voltar para as conversas</a></p>
    </div>
</body>
</html>
