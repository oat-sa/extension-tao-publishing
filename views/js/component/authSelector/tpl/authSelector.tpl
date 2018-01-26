<div class="auth-selector-component">
    <div class="auth-type-container">
        <label for="taoPlatformAuthType" class="form_desc">{{__ 'Auth type'}}</label>
        <select name="taoPlatformAuthType" id="taoPlatformAuthType" class="auth-type-selector">
            {{#each allowedTypes}}
            <option value="{{@key}}" {{#if selected}}selected="selected"{{/if}}>{{label}}</option>
            {{/each}}
        </select>
    </div>
    <div class="authenticator-settings"></div>
</div>
