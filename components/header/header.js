const configOptions = document.querySelector(".config-options");
const configButton = document.querySelector(".config-button");
const configImg = document.querySelector(".img-config");

if (configButton) {
  configButton.addEventListener("click", function () {
    configOptions.classList.toggle("active");
    if (configImg) configImg.classList.toggle("rotated");
  });
}

/* configButton.addEventListener('click', function() {
    console.log("aaaaaaaaaa");
    configOptions.classList.toggle('active');
}); */

// Pending friend requests indicator
const pendingStar = document.querySelector(".pending-star");

function updatePendingIndicator(count) {
  if (!pendingStar) return;
  if (count && Number(count) > 0) {
    pendingStar.textContent = "★";
    pendingStar.classList.add("visible");
    pendingStar.setAttribute("title", count + " pedido(s) de amizade");
  } else {
    pendingStar.textContent = "";
    pendingStar.classList.remove("visible");
    pendingStar.removeAttribute("title");
  }
}

async function fetchPending() {
  try {
    const res = await fetch(
      "/millenium/scripts/get_pending_friend_requests.php",
      { credentials: "same-origin" }
    );
    if (!res.ok) return;
    const data = await res.json();
    updatePendingIndicator(data.pending || 0);
  } catch (err) {
    // silently fail
  }
}

// check on load and poll periodically
fetchPending();
setInterval(fetchPending, 20000);

// Notificações
const notifCountEl = document.querySelector('.notif-count');
const notifBtn = document.querySelector('.notifications-button');
const notifList = document.querySelector('.notifications-list');

async function fetchNotifications() {
  try {
    const res = await fetch('/millenium/scripts/get_notifications.php', { credentials: 'same-origin' });
    if (!res.ok) return;
    const data = await res.json();
    const count = data.unread || 0;
    if (notifCountEl) {
      notifCountEl.textContent = count > 0 ? count : '';
    }
    if (notifList) {
      notifList.innerHTML = '';
      if (data.notifications && data.notifications.length) {
        // helper to append notif id to URL
        function appendNotifParam(url, id) {
          try {
            const u = new URL(url, window.location.origin);
            u.searchParams.set('notif', id);
            return u.toString();
          } catch (err) {
            // fallback: simple concat
            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'notif=' + encodeURIComponent(id);
          }
        }

        data.notifications.forEach(n => {
          const item = document.createElement('div');
          item.className = 'notif-item';
          item.style.padding = '8px';
          item.style.borderBottom = '1px solid #eee';
          // if the notification link points to perfil-pesquisado for the current user, rewrite to perfil.php
          let linkToUse = n.link;
          try {
            const m = /perfil-pesquisado\.php\?id=(\d+)/.exec(n.link);
            if (m && window.currentUserId && parseInt(m[1], 10) === parseInt(window.currentUserId, 10)) {
              linkToUse = '/millenium/paginas/perfil.php';
            }
          } catch (err) {
            // ignore
          }
          const safeHref = appendNotifParam(linkToUse, n.id);
          item.innerHTML = `<a href="${safeHref}" data-id="${n.id}" class="notif-link">${n.text}</a><br>`;
          notifList.appendChild(item);
        });
      } else {
        notifList.innerHTML = '<div style="padding:8px;color:#666">Nenhuma notificação</div>';
      }
    }
  } catch (err) {
    // ignore
  }
}

notifBtn && notifBtn.addEventListener('click', function(e) {
  if (notifList) {
    notifList.style.display = notifList.style.display === 'block' ? 'none' : 'block';
    if (notifList.style.display === 'block') fetchNotifications();
  }
});

// Ao carregar qualquer página, se houver ?notif=<id> na URL, solicita exclusão da notificação
function getQueryParam(name) {
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

async function deleteNotifIfPresent() {
  const nid = getQueryParam('notif');
  if (!nid) return;
  try {
    await fetch('/millenium/scripts/mark_notification_read.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'id=' + encodeURIComponent(nid) });
    // remove param from URL to keep it clean
    const url = new URL(window.location.href);
    url.searchParams.delete('notif');
    window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
    // refresh notification list/count
    fetchNotifications();
  } catch (err) {
    // ignore
  }
}

// run on load
deleteNotifIfPresent();

// poll notifications
fetchNotifications();
setInterval(fetchNotifications, 25000);
