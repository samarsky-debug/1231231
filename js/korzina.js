console.log('script loaded');

document.addEventListener('DOMContentLoaded', function() {
  (function() {
    // ---------- DOM элементы корзины ----------
    const cartIcon = document.getElementById('cartIconBtn');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartPanel = document.getElementById('cartPanel');
    const closeCart = document.getElementById('closeCart');
    const cartItemsDiv = document.getElementById('cartItems');
    const cartTotalSpan = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (!cartIcon || !cartOverlay || !cartPanel) return;

    // Глобальный массив товаров корзины (заполняется с сервера)
    let cart = [];

    // ---------- Вспомогательная функция экранирования HTML ----------
    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
      });
    }

    // ---------- Загрузка корзины с сервера (PHP + БД) ----------
    async function loadCart() {
      try {
        const response = await fetch('cart_handler.php?action=get');
        const data = await response.json();
        if (data.error) {
          console.error('Ошибка загрузки корзины:', data.error);
          return;
        }
        cart = data.items || [];
        renderCart();
        if (cartTotalSpan) {
          cartTotalSpan.textContent = (data.total || 0).toLocaleString() + ' ₽';
        }
      } catch (err) {
        console.error('Ошибка загрузки корзины:', err);
      }
    }

    // ---------- Добавление товара (отправка на сервер) ----------
    async function addToCart(product) {
      // product ожидается в формате { id, size, quantity? }
      const formData = new FormData();
      formData.append('action', 'add');
      formData.append('id', product.id);
      formData.append('size', product.size);
      formData.append('quantity', product.quantity || 1);

      try {
        const response = await fetch('cart_handler.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.error) {
          console.error('Ошибка добавления:', result.error);
          return;
        }
        // После успешного добавления перезагружаем корзину
        await loadCart();
      } catch (err) {
        console.error('Ошибка добавления:', err);
      }
    }

    // ---------- Изменение количества (delta: +1 / -1) ----------
    async function updateQuantity(cartId, delta) {
      const formData = new FormData();
      formData.append('action', 'update');
      formData.append('cartId', cartId);
      formData.append('delta', delta);

      try {
        const response = await fetch('cart_handler.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.error) {
          console.error('Ошибка обновления:', result.error);
          return;
        }
        await loadCart();
      } catch (err) {
        console.error('Ошибка обновления:', err);
      }
    }

    // ---------- Полное удаление товара ----------
    async function removeItem(cartId) {
      const formData = new FormData();
      formData.append('action', 'remove');
      formData.append('cartId', cartId);

      try {
        const response = await fetch('cart_handler.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.error) {
          console.error('Ошибка удаления:', result.error);
          return;
        }
        await loadCart();
      } catch (err) {
        console.error('Ошибка удаления:', err);
      }
    }

    // ---------- Отрисовка корзины (на основе глобальной переменной cart) ----------
    function renderCart() {
      if (!cartItemsDiv || !cartTotalSpan) return;

      if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p class="empty-cart">Корзина пуста</p>';
        cartTotalSpan.textContent = '0 ₽';
        return;
      }

      let html = '';
      let totalSum = 0;
      cart.forEach(item => {
        totalSum += item.price * item.quantity;
        html += `
          <div class="cart-item" data-cartid="${item.cartId}">
            <img src="${item.image}" alt="${item.name}" class="cart-item-img" onerror="this.src='../img/t-shirt.jpg'">
            <div class="cart-item-info">
              <div class="cart-item-title">${escapeHtml(item.name)}</div>
              <div class="cart-item-size">Размер: ${escapeHtml(item.size)}</div>
              <div class="cart-item-price">${item.price.toLocaleString()} ₽</div>
              <div class="cart-item-actions">
                <button class="decrease-qty">−</button>
                <span>${item.quantity}</span>
                <button class="increase-qty">+</button>
                <span class="remove-item" title="Удалить">🗑️</span>
              </div>
            </div>
          </div>
        `;
      });
      cartItemsDiv.innerHTML = html;
      cartTotalSpan.textContent = `${totalSum.toLocaleString()} ₽`;

      // Вешаем обработчики на кнопки управления количеством
      document.querySelectorAll('.decrease-qty').forEach(btn => {
        btn.removeEventListener('click', handleDecrease);
        btn.addEventListener('click', handleDecrease);
      });
      document.querySelectorAll('.increase-qty').forEach(btn => {
        btn.removeEventListener('click', handleIncrease);
        btn.addEventListener('click', handleIncrease);
      });
      document.querySelectorAll('.remove-item').forEach(btn => {
        btn.removeEventListener('click', handleRemove);
        btn.addEventListener('click', handleRemove);
      });
    }

    // Обработчики для кнопок корзины (вынесены, чтобы можно было удалять/добавлять)
    function handleDecrease(e) {
      const cartItemDiv = e.target.closest('.cart-item');
      if (cartItemDiv) updateQuantity(cartItemDiv.dataset.cartid, -1);
    }
    function handleIncrease(e) {
      const cartItemDiv = e.target.closest('.cart-item');
      if (cartItemDiv) updateQuantity(cartItemDiv.dataset.cartid, 1);
    }
    function handleRemove(e) {
      const cartItemDiv = e.target.closest('.cart-item');
      if (cartItemDiv) removeItem(cartItemDiv.dataset.cartid);
    }

    // ---------- Открытие / закрытие панели корзины ----------
    function openCart() {
      cartOverlay.classList.add('active');
      cartPanel.classList.add('active');
      document.body.style.overflow = 'hidden';
      // Обновляем содержимое при открытии
      loadCart();
    }

    function closeCartPanel() {
      cartOverlay.classList.remove('active');
      cartPanel.classList.remove('active');
      document.body.style.overflow = '';
    }

    cartIcon.addEventListener('click', openCart);
    closeCart.addEventListener('click', closeCartPanel);
    cartOverlay.addEventListener('click', closeCartPanel);

    if (checkoutBtn) {
      checkoutBtn.addEventListener('click', async () => {
        if (cart.length === 0) {
          alert('Ваша корзина пуста');
          return;
        }
        // Здесь вы можете отправить заказ на сервер
        alert('Спасибо за заказ! (демонстрация) Мы свяжемся с вами.');
        // Очищаем корзину на сервере
        const formData = new FormData();
        formData.append('action', 'clear');
        await fetch('cart_handler.php', { method: 'POST', body: formData });
        await loadCart();
        closeCartPanel();
      });
    }

    // ---------- Обработчик выбора размера на странице товара ----------
    const sizeBadges = document.querySelectorAll('.size-badge');
    sizeBadges.forEach(badge => {
      badge.addEventListener('click', function() {
        sizeBadges.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // ---------- Глобальный обработчик кнопок "В корзину" ----------
    document.body.addEventListener('click', async (e) => {
      const addBtn = e.target.closest('.add-to-cart, .add-to-cart-btn');
      if (!addBtn) return;
      e.preventDefault();

      let id = null;
      let size = 'M';   // размер по умолчанию

      // 1. Кнопка внутри страницы товара (.description-section)
      const productContainer = addBtn.closest('.description-section');
      if (productContainer) {
        id = productContainer.dataset.id;
        const activeSize = productContainer.querySelector('.size-badge.active');
        if (activeSize) size = activeSize.innerText.trim();
        else size = 'M'; // если нет выбранного размера
      } 
      // 2. Кнопка внутри карточки товара (.product) в каталоге
      else {
        const productCard = addBtn.closest('.product');
        if (productCard) {
          id = productCard.dataset.id;
          // Для каталога можно предложить выбор размера, но по умолчанию 'M'
          size = 'M';
        }
      }

      if (!id) {
        console.warn('Не удалось определить ID товара', addBtn);
        return;
      }

      // Добавляем товар
      await addToCart({ id: id, size: size, quantity: 1 });

      // Визуальный фидбек на кнопке
      const originalText = addBtn.innerText;
      addBtn.innerText = '✓ Добавлено';
      setTimeout(() => { addBtn.innerText = originalText; }, 800);
    });

    // ---------- Переключение главного изображения (миниатюры) ----------
    const mainImage = document.getElementById('main-img');
    const thumbnails = document.querySelectorAll('.thumbnails img');
    if (mainImage && thumbnails.length) {
      thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
          if (mainImage.classList.contains('fade-out')) return;
          const newSrc = this.src;
          mainImage.classList.add('fade-out');
          setTimeout(() => {
            mainImage.src = newSrc;
            mainImage.classList.remove('fade-out');
          }, 300);
          thumbnails.forEach(thumb => thumb.classList.remove('active'));
          this.classList.add('active');
        });
      });
    }

    // ---------- Первоначальная загрузка корзины ----------
    loadCart();
  })();
});