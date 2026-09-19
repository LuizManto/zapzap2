<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Entrar - zapzap2</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .caixa { background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.1); width: 320px; }
        h1 { font-size: 20px; margin-bottom: 20px; }
        label { display: block; margin-bottom: 4px; font-size: 14px; color: #333; }
        input { width: 100%; padding: 10px; margin-bottom: 14px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #25d366; color: #fff; border: none; border-radius: 4px; font-size: 15px; cursor: pointer; }
        .erro { background: #fdecea; color: #b3261e; padding: 8px; border-radius: 4px; margin-bottom: 14px; font-size: 14px; }
        .sucesso { background: #e6f6ea; color: #1e7b34; padding: 8px; border-radius: 4px; margin-bottom: 14px; font-size: 14px; }
        p { text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class="caixa">
        <h1>Entrar no zapzap2</h1>

        <?php if (session()->getFlashdata('sucesso')): ?>
            <div class="sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
        <?php endif; ?>

        <?php if (! empty($erro)): ?>
            <div class="erro"><?= esc($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="/login">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= esc($old['email'] ?? '') ?>" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit">Entrar</button>
        </form>

        <p>Não tem conta? <a href="/cadastro">Cadastre-se</a></p>
    </div>
</body>
</html>
