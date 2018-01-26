<div>
    <div class="login-container">
        <label for="login" class="form_desc">{{__ 'Login'}}</label>
        <input type="text" id="login" name="login" class="credential-field" value="{{ login }}">
    </div>
    <div>
        <label for="password">{{__ 'Password'}}</label>
        <input type="password" name="password" autocomplete="off"
               value="{{ password }}"
               class="credential-field">
    </div>
</div>
