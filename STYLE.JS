const products = [
	{ id: 1, name: "Mug Senja", category: "Rumah", price: 185000, label: "Paling disukai", image: "https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?auto=format&fit=crop&w=700&q=85" },
	{ id: 2, name: "Lilin Sore", category: "Ritual", price: 145000, label: "Baru", image: "https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=700&q=85" },
	{ id: 3, name: "Jurnal Hari Ini", category: "Kertas", price: 98000, label: "", image: "https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=700&q=85" },
	{ id: 4, name: "Vas Lembayung", category: "Rumah", price: 320000, label: "Edisi terbatas", image: "https://images.unsplash.com/photo-1581783898377-1c85bf937427?auto=format&fit=crop&w=700&q=85" },
	{ id: 5, name: "Teh Pagi", category: "Ritual", price: 125000, label: "", image: "https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=700&q=85" },
	{ id: 6, name: "Kartu Kecil", category: "Kertas", price: 65000, label: "", image: "https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=700&q=85" },
	{ id: 7, name: "Selimut Rona", category: "Rumah", price: 410000, label: "Favorit", image: "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=700&q=85" },
	{ id: 8, name: "Ruang Hening", category: "Ritual", price: 210000, label: "", image: "https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85?auto=format&fit=crop&w=700&q=85" }
];

const state = { category: "Semua", query: "", cart: [], wishlist: new Set() };
const money = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(value);
const grid = document.querySelector("#productGrid");
const emptyState = document.querySelector("#emptyState");

function renderProducts() {
	const sort = document.querySelector("#sortProducts").value;
	let visible = products.filter((product) => state.category === "Semua" || product.category === state.category);
	if (state.query) visible = visible.filter((product) => `${product.name} ${product.category}`.toLowerCase().includes(state.query.toLowerCase()));
	if (sort === "low") visible.sort((a, b) => a.price - b.price);
	if (sort === "high") visible.sort((a, b) => b.price - a.price);
	emptyState.hidden = visible.length > 0;
	grid.innerHTML = visible.map((product, index) => `<article class="product-card" style="animation-delay:${index * 45}ms"><div class="product-image"><img src="${product.image}" alt="${product.name}" loading="lazy">${product.label ? `<span class="product-badge">${product.label}</span>` : ""}<button class="wish-button ${state.wishlist.has(product.id) ? "saved" : ""}" data-wishlist="${product.id}" type="button" aria-label="${state.wishlist.has(product.id) ? "Hapus dari" : "Tambah ke"} wishlist">${state.wishlist.has(product.id) ? "♥" : "♡"}</button></div><div class="product-info"><div><h3 class="product-name">${product.name}</h3><p class="product-meta">${product.category}</p><button class="add-cart" data-add-cart="${product.id}" type="button">Tambah ke keranjang +</button></div><p class="product-price">${money(product.price)}</p></div></article>`).join("");
}

function updateCart() {
	const count = state.cart.reduce((total, item) => total + item.quantity, 0);
	document.querySelector("#cartCount").textContent = count;
	const cartItems = document.querySelector("#cartItems");
	if (!state.cart.length) cartItems.innerHTML = '<p class="cart-empty">Keranjangmu masih kosong.</p>';
	else cartItems.innerHTML = state.cart.map((item) => `<div class="cart-item"><img src="${item.image}" alt="${item.name}"><div><h3>${item.name}</h3><p>${money(item.price)}</p><div class="quantity"><button data-quantity="${item.id}:minus" type="button" aria-label="Kurangi jumlah">−</button><span>${item.quantity}</span><button data-quantity="${item.id}:plus" type="button" aria-label="Tambah jumlah">+</button></div></div><strong>${money(item.price * item.quantity)}</strong></div>`).join("");
	document.querySelector("#cartTotal").textContent = money(state.cart.reduce((total, item) => total + item.price * item.quantity, 0));
}

function showToast(message) { const toast = document.querySelector("#toast"); toast.textContent = message; toast.classList.add("show"); window.setTimeout(() => toast.classList.remove("show"), 2200); }
function setCategory(category) { state.category = category; document.querySelectorAll(".category-tab").forEach((tab) => tab.classList.toggle("active", tab.dataset.category === category)); document.querySelector("#koleksi").scrollIntoView({ behavior: "smooth", block: "start" }); renderProducts(); }
function openCart() { document.querySelector("#cartDrawer").classList.add("open"); document.querySelector("#cartDrawer").setAttribute("aria-hidden", "false"); document.querySelector("#overlay").hidden = false; }
function closeCart() { document.querySelector("#cartDrawer").classList.remove("open"); document.querySelector("#cartDrawer").setAttribute("aria-hidden", "true"); document.querySelector("#overlay").hidden = true; }

document.querySelectorAll(".category-tab").forEach((tab) => tab.addEventListener("click", () => setCategory(tab.dataset.category)));
document.querySelectorAll("[data-category-link]").forEach((button) => button.addEventListener("click", () => setCategory(button.dataset.categoryLink)));
document.querySelector("#sortProducts").addEventListener("change", renderProducts);
grid.addEventListener("click", (event) => { const addButton = event.target.closest("[data-add-cart]"); if (addButton) { const product = products.find((entry) => entry.id === Number(addButton.dataset.addCart)); const existing = state.cart.find((entry) => entry.id === product.id); if (existing) existing.quantity += 1; else state.cart.push({ ...product, quantity: 1 }); updateCart(); openCart(); return; } const wish = event.target.closest("[data-wishlist]"); if (!wish) return; const id = Number(wish.dataset.wishlist); state.wishlist.has(id) ? state.wishlist.delete(id) : state.wishlist.add(id); document.querySelector("#wishlistCount").textContent = state.wishlist.size; renderProducts(); showToast(state.wishlist.has(id) ? "Ditambahkan ke wishlist" : "Dihapus dari wishlist"); });
document.querySelector("#cartToggle").addEventListener("click", openCart);
document.querySelector("#cartClose").addEventListener("click", closeCart);
document.querySelector("#overlay").addEventListener("click", closeCart);
document.querySelector("#wishlistToggle").addEventListener("click", () => { document.querySelector("#koleksi").scrollIntoView({ behavior: "smooth" }); showToast(state.wishlist.size ? `${state.wishlist.size} item tersimpan di wishlist` : "Belum ada item di wishlist"); });
document.querySelector("#cartItems").addEventListener("click", (event) => { const control = event.target.closest("[data-quantity]"); if (!control) return; const [id, direction] = control.dataset.quantity.split(":"); const item = state.cart.find((entry) => entry.id === Number(id)); if (!item) return; item.quantity += direction === "plus" ? 1 : -1; state.cart = state.cart.filter((entry) => entry.quantity > 0); updateCart(); });
document.querySelector("#searchToggle").addEventListener("click", () => { const panel = document.querySelector("#searchPanel"); const isOpen = panel.classList.toggle("open"); panel.setAttribute("aria-hidden", String(!isOpen)); document.querySelector("#searchToggle").setAttribute("aria-expanded", String(isOpen)); if (isOpen) document.querySelector("#searchInput").focus(); });
document.querySelector("#searchInput").addEventListener("input", (event) => { state.query = event.target.value.trim(); renderProducts(); });
document.querySelector("#clearSearch").addEventListener("click", () => { document.querySelector("#searchInput").value = ""; state.query = ""; renderProducts(); });
document.querySelector("#menuToggle").addEventListener("click", () => { const nav = document.querySelector(".main-nav"); const open = nav.classList.toggle("mobile-open"); document.querySelector("#menuToggle").setAttribute("aria-expanded", String(open)); });
document.querySelector(".checkout-button").addEventListener("click", () => showToast(state.cart.length ? "Checkout siap dilanjutkan" : "Tambahkan produk terlebih dahulu"));
renderProducts(); updateCart();
