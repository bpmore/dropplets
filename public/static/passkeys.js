/* Passkey (WebAuthn) plumbing for the admin area. Loaded only on the
   settings page (registration) and the login page (sign-in). The server
   does all verification; this file just ferries buffers back and forth. */
(function () {
    'use strict';

    function b64uToBuf(s) {
        s = s.replace(/-/g, '+').replace(/_/g, '/');
        var pad = s.length % 4 ? '===='.slice(s.length % 4) : '';
        var bin = atob(s + pad);
        var bytes = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes.buffer;
    }

    function bufToB64u(buf) {
        var bytes = new Uint8Array(buf);
        var bin = '';
        for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    async function post(url, fields) {
        var res = await fetch(url, {
            method: 'POST',
            body: new URLSearchParams(fields),
            credentials: 'same-origin'
        });
        var json = await res.json().catch(function () { return {}; });
        if (!res.ok) throw new Error(json.error || 'Request failed (' + res.status + ')');
        return json;
    }

    function say(el, msg, isError) {
        if (el) {
            el.textContent = msg;
            el.className = 'small mb-0 ' + (isError ? 'text-danger' : 'text-muted');
        }
    }

    // ------------------------------------------------ registration (settings)
    var section = document.getElementById('passkeySection');
    if (section) {
        var addBtn = document.getElementById('passkeyAdd');
        var msg = document.getElementById('passkeyMsg');
        if (!window.PublicKeyCredential) {
            addBtn.disabled = true;
            say(msg, 'This browser does not support passkeys.', false);
        } else {
            addBtn.addEventListener('click', async function () {
                addBtn.disabled = true;
                say(msg, 'Follow your browser’s prompt…', false);
                try {
                    var args = await post(section.dataset.optionsUrl, {
                        csrf_token: section.dataset.csrf
                    });
                    var pk = args.publicKey;
                    pk.challenge = b64uToBuf(pk.challenge);
                    pk.user.id = b64uToBuf(pk.user.id);
                    (pk.excludeCredentials || []).forEach(function (c) { c.id = b64uToBuf(c.id); });
                    var cred = await navigator.credentials.create({ publicKey: pk });
                    await post(section.dataset.registerUrl, {
                        csrf_token: section.dataset.csrf,
                        label: (document.getElementById('passkeyLabel').value || '').trim(),
                        clientDataJSON: bufToB64u(cred.response.clientDataJSON),
                        attestationObject: bufToB64u(cred.response.attestationObject)
                    });
                    window.location.reload();
                } catch (e) {
                    say(msg, e.message || 'Could not add a passkey.', true);
                    addBtn.disabled = false;
                }
            });
        }
    }

    // ------------------------------------------------------- sign-in (login)
    var loginBtn = document.getElementById('passkeyLogin');
    if (loginBtn) {
        var loginMsg = document.getElementById('passkeyLoginMsg');
        if (!window.PublicKeyCredential) {
            loginBtn.hidden = true;
        } else {
            loginBtn.addEventListener('click', async function () {
                loginBtn.disabled = true;
                say(loginMsg, 'Follow your browser’s prompt…', false);
                try {
                    var args = await post(loginBtn.dataset.optionsUrl, {
                        csrf_token: loginBtn.dataset.csrf
                    });
                    var pk = args.publicKey;
                    pk.challenge = b64uToBuf(pk.challenge);
                    (pk.allowCredentials || []).forEach(function (c) { c.id = b64uToBuf(c.id); });
                    var cred = await navigator.credentials.get({ publicKey: pk });
                    var result = await post(loginBtn.dataset.verifyUrl, {
                        csrf_token: loginBtn.dataset.csrf,
                        id: cred.id,
                        clientDataJSON: bufToB64u(cred.response.clientDataJSON),
                        authenticatorData: bufToB64u(cred.response.authenticatorData),
                        signature: bufToB64u(cred.response.signature)
                    });
                    window.location.assign(result.redirect || '/');
                } catch (e) {
                    say(loginMsg, e.message || 'Passkey sign-in failed.', true);
                    loginBtn.disabled = false;
                }
            });
        }
    }
})();
