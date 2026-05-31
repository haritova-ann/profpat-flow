<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Вход в систему</title>

<link rel="stylesheet" href="/assets/css/forms.css">

<style>
.auth-wrapper {
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 20px;
}

.auth-card {
    width: 100%;
    max-width: 420px;

    background: white;

    border-radius: 18px;

    padding: 35px;

    box-shadow: 0 10px 35px rgba(0,0,0,0.08);
}

.auth-logo {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 10px;

    color: #2563eb;
}

.auth-subtitle {
    color: #64748b;
    margin-bottom: 30px;
    font-size: 14px;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.auth-btn {
    width: 100%;
    margin-top: 10px;

    padding: 14px;

    font-size: 15px;
    font-weight: 600;

    background: #2563eb;
    border-radius: 12px;
}

.auth-btn:hover {
    background: #1d4ed8;
}

.auth-footer {
    margin-top: 25px;

    text-align: center;

    font-size: 13px;
    color: #94a3b8;
}

.auth-icon {
    width: 64px;
    height: 64px;

    border-radius: 16px;

    background: #eff6ff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 28px;

    margin-bottom: 20px;
}
</style>

</head>

<body>

<div class="auth-wrapper">

    <div class="auth-card">

        <div class="auth-icon">
            🩺
        </div>

        <div class="auth-logo">
            Profpat Flow
        </div>

        <div class="auth-subtitle">
            Медицинская информационная система
        </div>

        <form method="POST" action="auth.php" class="auth-form">

            <div class="form-group">
                <label>Логин</label>

                <input 
                    type="text" 
                    name="login"
                    placeholder="Введите логин"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label>Пароль</label>

                <input 
                    type="password" 
                    name="password"
                    placeholder="Введите пароль"
                    required
                >
            </div>

            <button type="submit" class="auth-btn">
                Войти
            </button>

        </form>

        <div class="auth-footer">
            © <?= date('Y') ?> Profpat Flow
        </div>

    </div>

</div>

</body>
</html>