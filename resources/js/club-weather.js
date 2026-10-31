export default (url, date, today, opensAt, closesAt, city) => ({
    forecast: null,
    loading: true,
    message: '',
    init() {
        const controller = new AbortController();
        this.abortController = controller;
        const timeout = setTimeout(() => controller.abort(), 15000);
        fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then(response => { if (!response.ok) throw new Error(); return response.json(); })
            .then(data => {
                if (!data.available) { this.message = data.message; return; }
                this.forecast = data;
            })
            .catch(() => { this.message = 'El clima no está disponible por ahora. Puedes reservar igualmente.'; })
            .finally(() => { clearTimeout(timeout); this.loading = false; });
    },
    destroy() { this.abortController?.abort(); },
    get hours() {
        if (!this.forecast) return [];
        return Object.values(this.forecast.hours).filter(hour => this.minutes(hour.time) + 60 > this.minutes(opensAt) && this.minutes(hour.time) < this.minutes(closesAt));
    },
    get summary() {
        const now = new Intl.DateTimeFormat('en-GB', { timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date());
        return this.hours.find(hour => date !== today || hour.time >= now) || this.hours[0] || null;
    },
    get bestHour() {
        const now = new Intl.DateTimeFormat('en-GB', { timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date());
        return this.hours.filter(hour => hour.rain !== null && (date !== today || hour.time >= now))
            .sort((a, b) => a.rain - b.rain || (a.wind ?? Infinity) - (b.wind ?? Infinity))[0] || null;
    },
    dayLabel(day) {
        const item = this.forecast?.days?.[day];
        return item ? item.icon + ' ' + (item.max ?? '—') + '°' : '';
    },
    minutes(time) { const [hours, minutes] = time.split(':').map(Number); return hours * 60 + minutes; },
    weatherFor(start, end, courtCity = '') {
        if (courtCity && courtCity.trim().toLocaleLowerCase() !== city.trim().toLocaleLowerCase()) return null;
        const startMinute = this.minutes(start);
        const endMinute = this.minutes(end);
        const hours = this.hours.filter(hour => this.minutes(hour.time) < endMinute && this.minutes(hour.time) + 60 > startMinute);
        const expected = Math.ceil(endMinute / 60) - Math.floor(startMinute / 60);
        if (!hours.length || hours.length !== expected) return null;
        const complete = hours.filter(hour => hour.temperature !== null && hour.rain !== null && hour.wind !== null);
        if (complete.length !== hours.length) return null;
        const worst = complete.reduce((a, b) => a.rain >= b.rain ? a : b);
        return { ...worst, temperature: Math.round(complete.reduce((sum, hour) => sum + hour.temperature, 0) / complete.length), wind: Math.max(...complete.map(hour => hour.wind)) };
    },
    slotLabel(start, end, courtCity = '') {
        const item = this.weatherFor(start, end, courtCity);
        return item ? item.icon + ' ' + item.temperature + '° · 💧 ' + item.rain + '%' : '';
    },
});
