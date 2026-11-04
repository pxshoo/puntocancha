export default (value = '08:00') => ({
    time: value,
    open: false,
    get hour() { return /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(this.time) ? this.time.slice(0, 2) : '08'; },
    get minute() { return /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(this.time) ? this.time.slice(3, 5) : '00'; },
    setHour(hour) {
        if (!Number.isInteger(hour) || hour < 0 || hour > 23) return;
        this.time = String(hour).padStart(2, '0') + ':' + this.minute;
    },
    setMinute(minute) {
        if (!Number.isInteger(minute) || minute < 0 || minute > 59) return;
        this.time = this.hour + ':' + String(minute).padStart(2, '0');
        this.open = false;
        this.$refs?.toggle?.focus();
    },
});
