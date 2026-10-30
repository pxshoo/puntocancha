import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('posCart', (products, previousItems = [], method = 'cash') => ({
    products,
    cart: previousItems.map(item => {
        const product = products.find(product => product.id === Number(item.product_id));
        return product ? { ...product, quantity: Number(item.quantity) } : null;
    }).filter(Boolean),
    method,
    search: '',
    submitting: false,
    get filtered() {
        const search = this.search.toLocaleLowerCase();
        return this.products.filter(product =>
            [product.name, product.category, product.barcode ?? ''].some(value => value.toLocaleLowerCase().includes(search))
        );
    },
    get total() {
        return this.cart.reduce((sum, item) => sum + Number(item.price) * Number(item.quantity || 0), 0);
    },
    quantity(id) {
        return this.cart.find(item => item.id === id)?.quantity ?? 0;
    },
    add(product) {
        if (this.quantity(product.id) >= product.stock) return;
        const item = this.cart.find(item => item.id === product.id);
        if (item) item.quantity += 1;
        else this.cart.push({ ...product, quantity: 1 });
    },
    money(value) {
        return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 2 }).format(value);
    },
}));

Alpine.start();
