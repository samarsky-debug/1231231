document.addEventListener('DOMContentLoaded', function() {
  const authModal = document.getElementById('authModal');
  const profileModal = document.getElementById('profileModal');
  const loginRegisterBtn = document.getElementById('loginRegisterBtn');
  const profileTrigger = document.getElementById('profileTrigger');
  const logoutBtn = document.getElementById('logoutBtn');
  const closeBtns = document.querySelectorAll('.close-modal');
  const tabBtns = document.querySelectorAll('.tab-btn');
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  
  function openModal(modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
  
  closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      closeModal(authModal);
      closeModal(profileModal);
    });
  });
  window.addEventListener('click', (e) => {
    if (e.target === authModal) closeModal(authModal);
    if (e.target === profileModal) closeModal(profileModal);
  });
  
  if (loginRegisterBtn) {
    loginRegisterBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal(authModal);
    });
  }
  
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      tabBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      if (tab === 'login') {
        loginForm.classList.add('active');
        registerForm.classList.remove('active');
      } else {
        registerForm.classList.add('active');
        loginForm.classList.remove('active');
      }
    });
  });
  
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(loginForm);
    const response = await fetch('ajax/ajax_login.php', { method: 'POST', body: formData });
    const result = await response.json();
    const msgDiv = loginForm.querySelector('.form-message');
    if (result.success) {
      msgDiv.style.color = 'green';
      msgDiv.textContent = result.message;
      updateHeaderAfterLogin(result.username);
      closeModal(authModal);
      loginForm.reset();
    } else {
      msgDiv.style.color = 'red';
      msgDiv.textContent = result.message;
    }
  });
  
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(registerForm);
    const response = await fetch('ajax/ajax_register.php', { method: 'POST', body: formData });
    const result = await response.json();
    const msgDiv = registerForm.querySelector('.form-message');
    if (result.success) {
      msgDiv.style.color = 'green';
      msgDiv.textContent = result.message;
      updateHeaderAfterLogin(result.username);
      closeModal(authModal);
      registerForm.reset();
    } else {
      msgDiv.style.color = 'red';
      msgDiv.textContent = result.message;
    }
  });
  
  function updateHeaderAfterLogin(username) {
    const userLinks = document.getElementById('userLinks');
    if (userLinks) {
      userLinks.innerHTML = `
        <span class="profile-trigger" id="profileTrigger">Привет, ${escapeHtml(username)}!</span>
        <a href="#" id="logoutBtn">Выйти</a>
      `;
      attachProfileEvents();
      attachLogoutEvent();
    }
  }
  
  function updateHeaderAfterLogout() {
    const userLinks = document.getElementById('userLinks');
    if (userLinks) {
      userLinks.innerHTML = `<a href="#" id="loginRegisterBtn">Войти / Регистрация</a>`;
      const newBtn = document.getElementById('loginRegisterBtn');
      if (newBtn) {
        newBtn.addEventListener('click', (e) => {
          e.preventDefault();
          openModal(authModal);
        });
      }
    }
  }
  
  async function openProfileModal() {
    const response = await fetch('ajax/ajax_get_profile.php');
    const data = await response.json();
    if (data.success) {
      document.getElementById('profileUsername').textContent = data.username;
      document.getElementById('profileEmail').textContent = data.email;
      openModal(profileModal);
    } else {
      location.reload();
    }
  }
  
  async function handleLogout() {
    const response = await fetch('ajax/ajax_logout.php');
    const data = await response.json();
    if (data.success) {
      updateHeaderAfterLogout();
      closeModal(profileModal);
    }
  }
  
  function attachProfileEvents() {
    const newProfileTrigger = document.getElementById('profileTrigger');
    if (newProfileTrigger) newProfileTrigger.addEventListener('click', openProfileModal);
  }
  function attachLogoutEvent() {
    const newLogoutBtn = document.getElementById('logoutBtn');
    if (newLogoutBtn) {
      newLogoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        handleLogout();
      });
    }
  }
  
  if (profileTrigger) profileTrigger.addEventListener('click', openProfileModal);
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      handleLogout();
    });
  }
  
  const profileLogoutBtn = document.getElementById('profileLogoutBtn');
  if (profileLogoutBtn) profileLogoutBtn.addEventListener('click', () => handleLogout());
  
  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
      if (m === '&') return '&amp;';
      if (m === '<') return '&lt;';
      if (m === '>') return '&gt;';
      return m;
    });
  }
});