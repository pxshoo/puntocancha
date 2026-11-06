export default (endpoint, celebrate = false) => ({
    timer: null, redirectTimer: null, verified: false, paymentApproved: false, checking: false, notice: 'Comprobamos automáticamente si tu cuenta fue reactivada.',
    init() { this.check(); this.timer = setInterval(() => { if (!document.hidden) this.check(); }, 5000); },
    destroy() { clearInterval(this.timer); clearTimeout(this.redirectTimer); },
    async check(manual = false) {
        if (this.checking || this.verified) return;
        this.checking = true;
        if (manual) this.notice = 'Consultando el estado de tu pago...';
        try {
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: AbortSignal.timeout(8000) });
            if (response.status === 401) {
                clearInterval(this.timer);
                this.notice = 'Tu sesión venció. Vuelve a iniciar sesión para continuar.';
                return;
            }
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                this.notice = response.status === 403 ? 'Tu cuenta tiene el acceso suspendido. Contacta a soporte.' : 'No pudimos consultar el estado. Recarga la página e inténtalo nuevamente.';
                return;
            }
            const data = await response.json();
            if (data.enabled && data.redirect) {
                const target = new URL(data.redirect, window.location.origin);
                if (target.origin === window.location.origin) {
                    clearInterval(this.timer);
                    if (celebrate) {
                        this.verified = true;
                        this.paymentApproved = data.status === 'paid';
                        this.notice = this.paymentApproved ? 'Pago verificado. Configuración de club en proceso.' : 'Acceso habilitado por el administrador. Configuración de club en proceso.';
                        this.redirectTimer = setTimeout(() => window.location.assign(target.href), 2200);
                    } else { window.location.assign(target.href); }
                } else { this.notice = 'No pudimos abrir tu club. Recarga la página para continuar.'; }
            } else if (celebrate) {
                this.notice = data.status === 'manual_review'
                    ? 'Pago pendiente de aprobación del administrador. Todavía no se ha verificado. No vuelvas a pagar; esta página continuará consultando automáticamente.'
                    : data.status === 'failed'
                        ? 'Esta solicitud de pago fue rechazada. Contacta a soporte para revisarla.'
                        : 'Tu pago todavía no está habilitado para dar acceso. Contacta a soporte si ya fue aprobado.';
            }
        } catch { this.notice = 'No pudimos comprobar el acceso. Lo intentaremos de nuevo.'; }
        finally { this.checking = false; }
    },
});
