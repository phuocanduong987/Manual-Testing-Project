/**
 * Giỏ hàng demo phía trình duyệt (localStorage).
 * Đây thuần là cải tiến UI/UX — không thay thế luồng "Mua ngay" / checkout.php
 * hiện có, và không ảnh hưởng tới các endpoint phục vụ đề tài kiểm thử bảo mật.
 */
(function () {
  var STORAGE_KEY = 'minimart_cart';

  function readCart() {
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (e) {
      return [];
    }
  }

  function writeCart(items) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch (e) { /* ignore */ }
  }

  function cartCount(items) {
    return items.reduce(function (sum, it) { return sum + it.qty; }, 0);
  }

  function updateBadge() {
    var badge = document.getElementById('cart-badge');
    if (!badge) return;
    var count = cartCount(readCart());
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  }

  function addToCart(id, name, price) {
    var items = readCart();
    var existing = items.find(function (it) { return it.id === id; });
    if (existing) {
      existing.qty += 1;
    } else {
      items.push({ id: id, name: name, price: price, qty: 1 });
    }
    writeCart(items);
    updateBadge();
    return items;
  }

  function removeFromCart(id) {
    var items = readCart().filter(function (it) { return it.id !== id; });
    writeCart(items);
    updateBadge();
    renderCartPage();
  }

  function formatMoney(v) {
    return Number(v).toLocaleString('vi-VN') + 'đ';
  }

  var appliedVoucher = null; // { code, ... } khi áp dụng thành công

  function renderCartPage() {
    var mount = document.getElementById('cart-page-content');
    var checkoutPanel = document.getElementById('cart-checkout-panel');
    if (!mount) return;
    var items = readCart();

    if (items.length === 0) {
      mount.innerHTML = '<p class="muted">Giỏ hàng của bạn đang trống. <a href="index.php">Tiếp tục mua sắm</a>.</p>';
      if (checkoutPanel) checkoutPanel.style.display = 'none';
      return;
    }

    var subtotal = items.reduce(function (s, it) { return s + it.price * it.qty; }, 0);
    var rows = items.map(function (it) {
      return (
        '<tr>' +
        '<td>' + it.name + '</td>' +
        '<td><input type="number" min="1" value="' + it.qty + '" data-qty="' + it.id + '" style="width:60px;"></td>' +
        '<td>' + formatMoney(it.price) + '</td>' +
        '<td>' + formatMoney(it.price * it.qty) + '</td>' +
        '<td><a href="checkout.php?product_id=' + it.id + '" class="btn" style="margin-top:0;">Mua ngay</a> ' +
        '<button type="button" class="btn btn-secondary" style="margin-top:0;" data-remove="' + it.id + '">Xoá</button></td>' +
        '</tr>'
      );
    }).join('');

    mount.innerHTML =
      '<table><thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th><th></th></tr></thead>' +
      '<tbody>' + rows + '</tbody></table>' +
      '<p style="margin-top:16px;font-weight:700;">Tạm tính: ' + formatMoney(subtotal) + '</p>';

    mount.querySelectorAll('[data-remove]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        removeFromCart(Number(btn.getAttribute('data-remove')));
      });
    });
    mount.querySelectorAll('[data-qty]').forEach(function (input) {
      input.addEventListener('change', function () {
        var id = Number(input.getAttribute('data-qty'));
        var qty = Math.max(1, parseInt(input.value, 10) || 1);
        var cartItems = readCart();
        var target = cartItems.find(function (it) { return it.id === id; });
        if (target) {
          target.qty = qty;
          writeCart(cartItems);
        }
        updateBadge();
        renderCartPage();
        updateSummary();
      });
    });

    if (checkoutPanel) checkoutPanel.style.display = 'block';
    updateSummary();
  }

  function currentShippingFee(subtotal) {
    var select = document.getElementById('cart-zone');
    if (!select) return 0;
    var selected = select.options[select.selectedIndex];
    var fee = Number(selected ? selected.getAttribute('data-fee') || 0 : 0);
    if (subtotal >= 1000000) return 0; // miễn phí ship từ 1 triệu, khớp logic server
    return fee;
  }

  function updateSummary() {
    var summary = document.getElementById('cart-summary');
    if (!summary) return;
    var items = readCart();
    var subtotal = items.reduce(function (s, it) { return s + it.price * it.qty; }, 0);
    var shippingFee = currentShippingFee(subtotal);
    var discount = appliedVoucher ? appliedVoucher.discount : 0;
    var total = Math.max(0, subtotal + shippingFee - discount);

    var html = '<p>Tạm tính: ' + formatMoney(subtotal) + '</p>' +
      '<p>Phí vận chuyển: ' + formatMoney(shippingFee) + '</p>';
    if (discount > 0) {
      html += '<p>Giảm giá (' + appliedVoucher.code + '): -' + formatMoney(discount) + '</p>';
    }
    html += '<p style="font-weight:700;font-size:1.05rem;">Tổng thanh toán: ' + formatMoney(total) + '</p>';
    summary.innerHTML = html;
  }

  function setupCheckoutPanel() {
    var zoneSelect = document.getElementById('cart-zone');
    var applyBtn = document.getElementById('cart-apply-voucher');
    var buyAllBtn = document.getElementById('cart-buy-all');
    var errorBox = document.getElementById('cart-checkout-error');
    var msgBox = document.getElementById('cart-voucher-msg');

    if (zoneSelect) {
      // Gắn phí ship từng option để tính nhanh phía client (server sẽ tính lại chính xác khi đặt hàng).
      var fees = { 'noi-thanh': 20000, 'ngoai-thanh': 35000, 'tinh-khac': 50000 };
      Array.prototype.forEach.call(zoneSelect.options, function (opt) {
        opt.setAttribute('data-fee', fees[opt.value] || 0);
      });
      zoneSelect.addEventListener('change', updateSummary);
    }

    if (applyBtn) {
      applyBtn.addEventListener('click', function () {
        var code = (document.getElementById('cart-voucher').value || '').trim();
        var items = readCart();
        var subtotal = items.reduce(function (s, it) { return s + it.price * it.qty; }, 0);
        if (!code) {
          appliedVoucher = null;
          updateSummary();
          return;
        }
        fetch('api/validate_voucher.php?code=' + encodeURIComponent(code) + '&subtotal=' + subtotal)
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.ok) {
              appliedVoucher = { code: data.code, discount: data.discount };
              msgBox.textContent = data.message;
              msgBox.style.color = 'var(--ok)';
            } else {
              appliedVoucher = null;
              msgBox.textContent = data.message;
              msgBox.style.color = 'var(--danger)';
            }
            updateSummary();
          })
          .catch(function () {
            msgBox.textContent = 'Không thể kiểm tra voucher lúc này.';
          });
      });
    }

    if (buyAllBtn) {
      buyAllBtn.addEventListener('click', function () {
        var items = readCart();
        if (items.length === 0) return;
        var address = (document.getElementById('cart-address').value || '').trim();
        var phoneInput = document.getElementById('cart-phone');
        var phone = (phoneInput && phoneInput.value ? phoneInput.value : '').trim();
        var zone = zoneSelect ? zoneSelect.value : '';
        errorBox.style.display = 'none';

        if (!address || !phone) {
          errorBox.textContent = 'Vui lòng nhập đầy đủ địa chỉ giao hàng và số điện thoại.';
          errorBox.style.display = 'block';
          return;
        }

        if (!/^[0-9+ ]{8,15}$/.test(phone)) {
          errorBox.textContent = 'Số điện thoại không hợp lệ.';
          errorBox.style.display = 'block';
          return;
        }

        buyAllBtn.disabled = true;
        buyAllBtn.textContent = 'Đang xử lý...';

        fetch('checkout_cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            items: items.map(function (it) { return { id: it.id, qty: it.qty }; }),
            address: address,
            phone: phone,
            zone: zone,
            voucher_code: appliedVoucher ? appliedVoucher.code : ''
          })
        })
          .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
          .then(function (res) {
            if (res.status >= 200 && res.status < 300) {
              writeCart([]);
              updateBadge();
              window.location.href = 'orders.php?id=' + res.data.order_id;
            } else {
              errorBox.textContent = res.data.error || 'Có lỗi xảy ra, vui lòng thử lại.';
              errorBox.style.display = 'block';
              buyAllBtn.disabled = false;
              buyAllBtn.textContent = 'Mua tất cả';
            }
          })
          .catch(function () {
            errorBox.textContent = 'Không thể kết nối tới máy chủ.';
            errorBox.style.display = 'block';
            buyAllBtn.disabled = false;
            buyAllBtn.textContent = 'Mua tất cả';
          });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateBadge();
    renderCartPage();
    setupCheckoutPanel();

    document.querySelectorAll('[data-add-to-cart]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = Number(btn.getAttribute('data-id'));
        var name = btn.getAttribute('data-name');
        var price = Number(btn.getAttribute('data-price'));
        addToCart(id, name, price);
        var original = btn.textContent;
        btn.textContent = '✓';
        setTimeout(function () { btn.textContent = original; }, 700);
      });
    });
  });

  window.TechNestCart = { addToCart: addToCart, removeFromCart: removeFromCart };
})();
