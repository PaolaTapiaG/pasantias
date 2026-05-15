<div class="mt-6 grid gap-6">
    <div class="grid gap-5">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Driver SMS</label>
            <select name="sms_driver" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <option value="log" @selected(old('sms_driver', $messaging['sms_driver'] ?? 'log') === 'log')>Local / log</option>
                <option value="android_gateway" @selected(old('sms_driver', $messaging['sms_driver'] ?? 'log') === 'android_gateway')>Gateway propio Android</option>
                <option value="twilio" @selected(old('sms_driver', $messaging['sms_driver'] ?? 'log') === 'twilio')>Twilio</option>
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Proveedor de gateway</label>
            <select name="sms_gateway_provider" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <option value="smsgate" @selected(old('sms_gateway_provider', $messaging['sms_gateway_provider'] ?? 'smsgate') === 'smsgate')>SMS Gateway for Android</option>
                <option value="generic" @selected(old('sms_gateway_provider', $messaging['sms_gateway_provider'] ?? 'smsgate') === 'generic')>Gateway HTTP generico</option>
            </select>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Codigo de pais</label>
                <input name="sms_country_code" value="{{ old('sms_country_code', $messaging['sms_country_code'] ?? '+591') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre remitente</label>
                <input name="sms_sender_name" value="{{ old('sms_sender_name', $messaging['sms_sender_name'] ?? 'EPSAS') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">URL del gateway SMS</label>
            <input name="sms_gateway_url" value="{{ old('sms_gateway_url', $messaging['sms_gateway_url'] ?? '') }}" placeholder="https://api.sms-gate.app/3rdparty/v1/message" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Usuario del gateway</label>
                <input name="sms_gateway_username" value="{{ old('sms_gateway_username', $messaging['sms_gateway_username'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Contrasena del gateway</label>
                <input name="sms_gateway_password" value="{{ old('sms_gateway_password', $messaging['sms_gateway_password'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Token/API key</label>
                <input name="sms_gateway_api_key" value="{{ old('sms_gateway_api_key', $messaging['sms_gateway_api_key'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Device ID</label>
                <input name="sms_gateway_device_id" value="{{ old('sms_gateway_device_id', $messaging['sms_gateway_device_id'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
        </div>
    </div>

    <div class="border-t border-slate-200 pt-6 dark:border-slate-700">
        <h3 class="theme-text text-lg font-semibold text-slate-900">Correo real</h3>
        <div class="mt-5 grid gap-5">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Mailer</label>
                <select name="mail_mailer" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <option value="log" @selected(old('mail_mailer', $mail['mailer'] ?? 'log') === 'log')>Solo log</option>
                    <option value="smtp" @selected(old('mail_mailer', $mail['mailer'] ?? 'log') === 'smtp')>SMTP real</option>
                    <option value="brevo_api" @selected(old('mail_mailer', $mail['mailer'] ?? 'log') === 'brevo_api')>Brevo API</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Brevo API key</label>
                <input name="mail_api_key" value="{{ old('mail_api_key', $mail['api_key'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">SMTP host</label>
                <input name="mail_host" value="{{ old('mail_host', $mail['host'] ?? '') }}" placeholder="smtp-relay.brevo.com" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Puerto</label>
                    <input name="mail_port" value="{{ old('mail_port', $mail['port'] ?? 587) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Cifrado</label>
                    <select name="mail_encryption" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="tls" @selected(old('mail_encryption', $mail['encryption'] ?? 'tls') === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('mail_encryption', $mail['encryption'] ?? 'tls') === 'ssl')>SSL</option>
                        <option value="null" @selected(empty(old('mail_encryption', $mail['encryption'] ?? 'tls')))>Sin cifrado</option>
                    </select>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Usuario SMTP</label>
                    <input name="mail_username" value="{{ old('mail_username', $mail['username'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Contrasena SMTP</label>
                    <input name="mail_password" value="{{ old('mail_password', $mail['password'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Correo remitente</label>
                    <input name="mail_from_address" value="{{ old('mail_from_address', $mail['from_address'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre remitente</label>
                    <input name="mail_from_name" value="{{ old('mail_from_name', $mail['from_name'] ?? 'EPSAS') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-200 pt-6 dark:border-slate-700">
        <h3 class="theme-text text-lg font-semibold text-slate-900">Opciones</h3>
        <div class="mt-5 space-y-4">
            <label class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700">
                <div>
                    <p class="theme-text text-sm font-medium text-slate-900">SMS habilitado</p>
                    <p class="theme-muted text-xs text-slate-500">Permite enviar mensajes desde el gateway.</p>
                </div>
                <input type="checkbox" name="sms_enabled" value="1" @checked(old('sms_enabled', $messaging['sms_enabled'] ?? false)) class="h-5 w-5 rounded border-slate-300 text-blue-600">
            </label>
            <label class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700">
                <div>
                    <p class="theme-text text-sm font-medium text-slate-900">Correo habilitado</p>
                    <p class="theme-muted text-xs text-slate-500">Mantiene disponible el canal de email.</p>
                </div>
                <input type="checkbox" name="email_enabled" value="1" @checked(old('email_enabled', $messaging['email_enabled'] ?? false)) class="h-5 w-5 rounded border-slate-300 text-blue-600">
            </label>
            <label class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700">
                <div>
                    <p class="theme-text text-sm font-medium text-slate-900">Modo mantenimiento</p>
                    <p class="theme-muted text-xs text-slate-500">Marca el sistema como bajo ajustes internos.</p>
                </div>
                <input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $system['maintenance_mode'] ?? false)) class="h-5 w-5 rounded border-slate-300 text-blue-600">
            </label>
        </div>
    </div>
</div>
