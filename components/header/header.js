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
