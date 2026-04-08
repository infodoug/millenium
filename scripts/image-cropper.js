// Simple cropper modal with pan and zoom. Produces a square (1:1) PNG dataURL.
function showImageCropper(file) {
  return new Promise((resolve, reject) => {
    if (!file) return reject('No file');
    const reader = new FileReader();
    reader.onload = function(e) {
      const dataUrl = e.target.result;
      const overlay = document.createElement('div');
      overlay.className = 'img-cropper-overlay';

      const modal = document.createElement('div');
      modal.className = 'img-cropper-modal';

      const stage = document.createElement('div');
      stage.className = 'img-cropper-stage';
      const img = document.createElement('img');
      img.src = dataUrl;
      stage.appendChild(img);
      modal.appendChild(stage);

      const actions = document.createElement('div');
      actions.className = 'img-cropper-actions';
      const cancelBtn = document.createElement('button'); cancelBtn.type='button'; cancelBtn.textContent='Cancelar';
      const acceptBtn = document.createElement('button'); acceptBtn.type='button'; acceptBtn.textContent='Usar imagem';
      actions.appendChild(cancelBtn); actions.appendChild(acceptBtn);
      modal.appendChild(actions);

      overlay.appendChild(modal);
      document.body.appendChild(overlay);

      let naturalW=0, naturalH=0;
      img.onload = function(){
        naturalW = img.naturalWidth; naturalH = img.naturalHeight;
        const stageW = stage.clientWidth; const stageH = stage.clientHeight;
        // initial scale to cover
        const scale = Math.max(stageW / naturalW, stageH / naturalH);
        img.style.width = (naturalW * scale) + 'px';
        img.style.height = (naturalH * scale) + 'px';
        img.style.left = (stageW - naturalW * scale)/2 + 'px';
        img.style.top = (stageH - naturalH * scale)/2 + 'px';
        img.dataset.scale = scale;
      }

      // Drag
      let dragging=false, startX=0, startY=0, imgStartLeft=0, imgStartTop=0;
      function px(v){ return parseFloat(v||'0'); }
      function down(e){
        dragging=true; startX = (e.touches?e.touches[0].clientX:e.clientX); startY = (e.touches?e.touches[0].clientY:e.clientY);
        imgStartLeft = px(img.style.left); imgStartTop = px(img.style.top);
        e.preventDefault();
      }
      function move(e){ if(!dragging) return; const mx = (e.touches?e.touches[0].clientX:e.clientX); const my = (e.touches?e.touches[0].clientY:e.clientY); const dx = mx-startX; const dy = my-startY; let newLeft = imgStartLeft+dx; let newTop = imgStartTop+dy; const stageW=stage.clientWidth, stageH=stage.clientHeight, imgW=img.clientWidth, imgH=img.clientHeight; newLeft = Math.min(0, Math.max(stageW-imgW, newLeft)); newTop = Math.min(0, Math.max(stageH-imgH, newTop)); img.style.left = newLeft+'px'; img.style.top = newTop+'px'; }
      function up(){ dragging=false; }
      img.addEventListener('mousedown', down); window.addEventListener('mousemove', move); window.addEventListener('mouseup', up);
      img.addEventListener('touchstart', down, {passive:false}); window.addEventListener('touchmove', move, {passive:false}); window.addEventListener('touchend', up);

      // Zoom with wheel
      function wheel(e){ e.preventDefault(); const delta = -e.deltaY; let scale = parseFloat(img.dataset.scale||1); const factor = delta>0?1.08:0.925; const newScale = Math.max(0.2, Math.min(5, scale * factor)); const stageW=stage.clientWidth, stageH=stage.clientHeight; // compute mouse position relative to image
        const rect = stage.getBoundingClientRect(); const mx = e.clientX - rect.left; const my = e.clientY - rect.top; const imgLeft = px(img.style.left), imgTop = px(img.style.top); const relX = (mx - imgLeft) / (img.clientWidth); const relY = (my - imgTop) / (img.clientHeight);
        // update size
        const naturalW_ = naturalW, naturalH_ = naturalH;
        const newW = naturalW_ * newScale; const newH = naturalH_ * newScale;
        // keep point under cursor stable
        const newLeft = mx - relX * newW; const newTop = my - relY * newH;
        img.style.width = newW + 'px'; img.style.height = newH + 'px'; img.style.left = Math.min(0, Math.max(stageW - newW, newLeft)) + 'px'; img.style.top = Math.min(0, Math.max(stageH - newH, newTop)) + 'px'; img.dataset.scale = newScale;
      }
      stage.addEventListener('wheel', wheel, {passive:false});

      cancelBtn.addEventListener('click', ()=>{ cleanup(); reject('cancel'); });
      acceptBtn.addEventListener('click', ()=>{
        // crop square from stage
        const size = Math.min(stage.clientWidth, stage.clientHeight);
        const canvas = document.createElement('canvas'); canvas.width = size; canvas.height = size; const ctx = canvas.getContext('2d'); const scaleX = naturalW / img.clientWidth; const scaleY = naturalH / img.clientHeight; const sx = Math.max(0, -px(img.style.left)) * scaleX; const sy = Math.max(0, -px(img.style.top)) * scaleY; const sSize = size * scaleX; ctx.drawImage(img, sx, sy, sSize, sSize, 0, 0, size, size); const out = canvas.toDataURL('image/png'); cleanup(); resolve(out);
      });

      function cleanup(){ img.removeEventListener('mousedown', down); window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', up); img.removeEventListener('touchstart', down); window.removeEventListener('touchmove', move); window.removeEventListener('touchend', up); stage.removeEventListener('wheel', wheel); document.body.removeChild(overlay); }
    };
    reader.readAsDataURL(file);
  });
}

// Convenience: bind file input to cropper and set hidden input; optionally auto-submit form
function bindInputToCropper(inputEl, hiddenEl, previewEl, autoSubmitForm){
  inputEl.addEventListener('change', function(){ const f = this.files[0]; if(!f) return; showImageCropper(f).then(dataUrl=>{ hiddenEl.value = dataUrl; if(previewEl){ previewEl.innerHTML = ''; const im = document.createElement('img'); im.src = dataUrl; im.style.width='100%'; im.style.height='100%'; im.style.objectFit='cover'; im.style.borderRadius='50%'; previewEl.appendChild(im); } if(autoSubmitForm){ const form = inputEl.closest('form') || document.getElementById(autoSubmitForm); if(form) form.submit(); } }).catch(()=>{}); });
}
