

document.documentElement.classList.remove('no-js');

const map    = (v,a1,a2,b1=0,b2=1)=>Math.min(Math.max((v-a1)/(a2-a1),0),1)*(b2-b1)+b1;
const smooth = t=>t*t*(3-2*t);
const easeOut= t=>t*(2-t);

(function () {
  function siteRootFromPath() {
    const p = location.pathname;
    if (p.includes('/subpaginas/')) return p.split('/subpaginas/')[0] + '/';
    return p.endsWith('/') ? p : p.replace(/[^/]*$/, '');
  }
  function updateNav(logged) {
    const showGuest = !logged;
    document.querySelectorAll('.nav-when-guest')
      .forEach(n => n.style.display = showGuest ? '' : 'none');
    document.querySelectorAll('.nav-when-logged')
      .forEach(n => n.style.display = showGuest ? 'none' : '');
  }
  function run() {
    const endpoint = siteRootFromPath() + 'subpaginas/session-status.php';
    fetch(endpoint, { cache: 'no-store', credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(s => updateNav(!!s.logged))
      .catch(() => {});
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else {
    run();
  }
})();

window.showToast = function(msg, ms=2600){
  let t = document.createElement('div');
  t.className = 'toast is-visible';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(()=> t.classList.add('is-hidden'), ms);
  setTimeout(()=> t.remove(), ms+400);
};
(function(){
  const p = new URLSearchParams(location.search);
  const ok = p.get('ok');
  const er = p.get('err');
  if (ok === 'login')    showToast('Sesión iniciada.');
  if (ok === 'register') showToast('Cuenta creada. ¡Bienvenid@!');
  if (ok === 'logout')   showToast('Cerraste sesión.');
  if (er)                showToast(er);
})();

const header = document.querySelector('.hdr.glass');
function setNavElevation(){ header && header.classList.toggle('is-scrolled', window.scrollY>8); }
document.addEventListener('scroll', setNavElevation, {passive:true});
document.addEventListener('DOMContentLoaded', setNavElevation);

document.querySelectorAll('.portrait').forEach(card=>{
  const prefersHover = window.matchMedia('(hover:hover)').matches;
  function toggle(){ if(!prefersHover) card.classList.toggle('reveal'); }
  card.addEventListener('click', toggle);
  card.addEventListener('keydown', e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); toggle(); } });
});

(function(){
  const hero = document.getElementById('hero');
  if (!hero) return;

  const root   = document.documentElement;
  const ph1    = hero.querySelector('.phrase--1');
  const ph2    = hero.querySelector('.phrase--2');
  const blob   = document.getElementById('hero-blob');
  const title  = hero.querySelector('.title');
  const words  = Array.from(hero.querySelectorAll('.word'));

  words.forEach(w=>{
    if (w.querySelector('.char')) return;
    const txt = w.textContent; w.textContent='';
    [...txt].forEach(ch=>{
      const s=document.createElement('span');
      s.className='char'; s.textContent=ch;
      w.appendChild(s);
    });
  });

  function progress(){
    const total = hero.scrollHeight - innerHeight;
    const start = hero.offsetTop;
    const y = Math.min(Math.max(scrollY - start, 0), total);
    return total ? y/total : 0;
  }

  let ticking=false;
  function onScroll(){ if(!ticking){ ticking=true; requestAnimationFrame(update); } }
  addEventListener('scroll', onScroll, {passive:true});
  addEventListener('resize', onScroll);
  addEventListener('DOMContentLoaded', onScroll);

  function update(){
    const p = progress();
    const q = smooth(p);

    root.style.setProperty('--hero-pan',      `${Math.round(q*40)}%`);
    root.style.setProperty('--hero-zoom',     (1 + q*0.12).toFixed(3));
    root.style.setProperty('--hero-parallax-y', `${(q*18).toFixed(1)}px`);

    const a=0.05, b=0.14;
    const tBase = Math.min(Math.max((p-a)/(b-a), 0), 1);
    const stagger = 0.30;
    const tNorm = tBase * (1 + stagger);
    let allGone = true;
    words.forEach(w=>{
      const chars = w.querySelectorAll('.char');
      const N = Math.max(1, chars.length-1);
      chars.forEach((ch,i)=>{
        const delay = (i/N) * stagger;
        const tt = Math.min(Math.max(tNorm - delay, 0), 1);
        ch.style.opacity   = String(1 - tt);
        ch.style.transform = `translateY(${-52*tt}px)`;
        if (tt < 1) allGone = false;
      });
    });
    if (title) title.style.visibility = allGone ? 'hidden' : 'visible';

    const p1 = Math.min(Math.max((p-0.34)/(0.56-0.34), 0), 1);
    ph1 && (ph1.style.opacity=p1, ph1.style.transform=`translate3d(0, ${(1-p1)*18}px, 0)`);
    const p2 = Math.min(Math.max((p-0.56)/(0.86-0.56), 0), 1);
    ph2 && (ph2.style.opacity=p2, ph2.style.transform=`translate3d(0, ${(1-p2)*18}px, 0)`);

    if (blob){
      const tb = Math.min(Math.max((p - 0.10) / (0.24 - 0.10), 0), 1);
      blob.style.opacity = String(1 - tb);
      blob.style.transform = `scale(${1 - tb*0.12})`;
    }

    ticking=false;
  }
})();

(function(){
  const mount = document.getElementById('hero-blob');
  if (!mount || !window.THREE) return;

  const { Scene, PerspectiveCamera, WebGLRenderer, Group,
          DirectionalLight, AmbientLight, IcosahedronGeometry,
          MeshStandardMaterial, Mesh } = THREE;

  const scene = new Scene();
  const camera = new PerspectiveCamera(35, 1, 0.1, 100);
  camera.position.set(0,0,6);

  const renderer = new WebGLRenderer({ antialias:true, alpha:true });
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  mount.appendChild(renderer.domElement);

  const group = new Group(); scene.add(group);
  const key = new DirectionalLight(0xffffff,1); key.position.set(2,3,4);
  const fill= new DirectionalLight(0x88aaff,.6); fill.position.set(-3,-2,-1);
  const rim = new DirectionalLight(0xff88aa,.5); rim.position.set(0,2,-3);
  scene.add(key, fill, rim, new AmbientLight(0xffffff,.35));

  const geo  = new IcosahedronGeometry(1.35, 6);
  const base = geo.attributes.position.array.slice();
  const rnd  = new Float32Array(geo.attributes.position.count).map(()=>Math.random()*Math.PI*2);
  const mat  = new MeshStandardMaterial({ color:0x0c0f14, roughness:.25, metalness:.35 });
  const mesh = new Mesh(geo, mat); group.add(mesh);

  function onResize(){
    const r = mount.getBoundingClientRect();
    const size = Math.min(r.width || 560, r.height || r.width || 560);
    renderer.setSize(size, size); camera.aspect = 1; camera.updateProjectionMatrix();
  }
  addEventListener('resize', onResize, {passive:true}); onResize();

  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  let t0 = performance.now();
  (function anim(now){
    const t = (now - t0) * 0.001;
    const pos = geo.attributes.position.array;
    const amp = 0.16, speed = 0.9;
    for(let i=0;i<pos.length;i+=3){
      const i3=i/3, bx=base[i], by=base[i+1], bz=base[i+2];
      const len = Math.hypot(bx,by,bz) || 1;
      const r = Math.sin(rnd[i3] + t*speed) * amp;
      pos[i]=bx+(bx/len)*r; pos[i+1]=by+(by/len)*r; pos[i+2]=bz+(bz/len)*r;
    }
    geo.attributes.position.needsUpdate = true; geo.computeVertexNormals();
    if(!reduce){ mesh.rotation.x += .003; mesh.rotation.y += .004; group.position.y = Math.sin(t*.8)*.15; }
    renderer.render(scene, camera);
    requestAnimationFrame(anim);
  })(t0);
})();

(function(){
  const frame=document.getElementById('artFrame'); if(!frame) return;
  const slides=[...frame.querySelectorAll('.slide')];
  if (slides.length <= 1) return;
  let i=0, timer=null, paused=false;
  const TICK=800;
  function show(n){ slides.forEach((s,idx)=>s.classList.toggle('show', idx===n)); }
  function next(){ i=(i+1)%slides.length; show(i); }
  function start(){ if(!timer && !paused) timer=setInterval(next,TICK); }
  function stop(){ if(timer){clearInterval(timer); timer=null;} }
  show(i); start();
  frame.addEventListener('mouseenter',()=>{paused=true; stop();});
  frame.addEventListener('mouseleave',()=>{paused=false; start();});
  frame.addEventListener('focusin', ()=>{paused=true; stop();});
  frame.addEventListener('focusout',()=>{paused=false; start();});
  frame.addEventListener('keydown',e=>{ if(e.key===' '||e.key==='Enter'){ e.preventDefault(); timer?stop():start(); } });
})();

(function(){
  const root=document.getElementById('tickerToday'); if(!root) return;
  const rows=root.querySelectorAll('.ticker__row');
  function buildRow(row){
    const track=row.querySelector('.ticker__track');
    const unit=track.querySelector('.tick');
    track.querySelectorAll('.tick:not(:first-child)').forEach(n=>n.remove());
    const gap=parseFloat(getComputedStyle(unit).marginRight)||0;
    const ghost=unit.cloneNode(true); ghost.style.position='absolute'; ghost.style.visibility='hidden';
    track.appendChild(ghost);
    const unitW=Math.round(ghost.getBoundingClientRect().width); ghost.remove();
    const singleWidth=unitW+gap;
    track.appendChild(unit.cloneNode(true));
    const PX_PER_SEC=130; const duration=singleWidth/PX_PER_SEC;
    track.style.setProperty('--marquee-w', singleWidth+'px');
    track.style.setProperty('--ticker-duration', duration+'s');
  }
  function buildAll(){ rows.forEach(buildRow); }
  if(document.fonts && document.fonts.ready){ document.fonts.ready.then(buildAll); }
  else{ window.addEventListener('load', buildAll); }
  window.addEventListener('resize', buildAll);
})();

(function(){
  const root = document.getElementById('microMosaic');
  if (!root) return;

  const rows = {
    r1: ['grid1.jpg','grid2.jpg','grid3.jpg','grid4.jpg','grid5.jpg','grid6.jpg','grid7.jpg','grid8.jpg','grid9.jpg','grid10.jpg'],
    r2: ['grid11.jpg','grid12.jpg','grid13.jpg','grid14.jpg','grid15.jpg','grid16.jpg','grid17.jpg','grid18.jpg','grid19.jpg'],
    r3: ['grid20.jpg','grid21.jpg','grid22.jpg','grid23.jpg','grid24.jpg','grid25.jpg','grid26.jpg','grid27.jpg'],
    r4: ['grid28.jpg','grid29.jpg','grid30.jpg','grid31.jpg','grid32.jpg','grid33.jpg','grid34.jpg'],
    r5: ['grid35.jpg','grid36.jpg','grid37.jpg','grid38.jpg','grid39.jpg','grid40.jpg']
  };

  const prefix = location.pathname.includes('/subpaginas/') ? '../assets/' : 'assets/';

  Object.entries(rows).forEach(([cls, arr])=>{
    const container = root.querySelector('.'+cls);
    if (!container) return;
    container.innerHTML='';
    arr.forEach(name=>{
      const img = document.createElement('img');
      img.src = prefix + name;
      img.alt = '';
      img.loading = 'lazy';
      img.decoding = 'async';
      container.appendChild(img);
    });
  });
})();

(() => {
  const start = () => {
    const root = document.getElementById('obCarousel');
    if (!root) return;

    const $$ = (sel) => root ? root.querySelectorAll(sel) : [];
    const $  = (sel) => root ? root.querySelector(sel) : null;

    const works = [
      { name: 'La persistencia de la memoria', role: 'Salvador Dalí' },
      { name: 'El hijo del hombre',            role: 'René Magritte' },
      { name: 'El gran masturbador',           role: 'Salvador Dalí' },
      { name: 'El elefante Celebes',           role: 'Max Ernst' },
      { name: 'Europe After the Rain II',      role: 'Max Ernst' },
      { name: 'Mujer, pájaro, estrella',       role: 'Joan Miró' },
    ];

    const cards      = $$('.obc-card');
    const dots       = $$('.obc-dot');
    const nameEl     = $('.obc-name');
    const artistEl   = $('.obc-artist');
    const leftArrow  = $('.obc-arrow.left');
    const rightArrow = $('.obc-arrow.right');

    if (!cards.length) return;

    let currentIndex = 0;
    let isAnimating  = false;

    function paint(){
      const n = cards.length;
      cards.forEach((card, i) => {
        const off = (i - currentIndex + n) % n;
        card.className = 'obc-card';
        if      (off === 0)     card.classList.add('center');
        else if (off === 1)     card.classList.add('right-1');
        else if (off === 2)     card.classList.add('right-2');
        else if (off === n-1)   card.classList.add('left-1');
        else if (off === n-2)   card.classList.add('left-2');
        else                    card.classList.add('hidden');
      });
      if (nameEl && artistEl){
        nameEl.style.opacity = artistEl.style.opacity = '0';
        setTimeout(()=>{
          nameEl.textContent = works[currentIndex].name;
          artistEl.textContent= works[currentIndex].role;
          nameEl.style.opacity = artistEl.style.opacity = '1';
        }, 120);
      }
      dots.forEach((d,i)=>{
        const on = i === currentIndex;
        d.classList.toggle('active', on);
        d.setAttribute('aria-selected', on ? 'true' : 'false');
      });
    }

    function go(newIndex){
      if (isAnimating) return;
      isAnimating = true;
      currentIndex = (newIndex + cards.length) % cards.length;
      paint();
      setTimeout(() => { isAnimating = false; }, 300);
    }

    paint();
    leftArrow  && leftArrow.addEventListener('click', () => go(currentIndex - 1));
    rightArrow && rightArrow.addEventListener('click', () => go(currentIndex + 1));
    dots.forEach((d,i) => d.addEventListener('click', () => go(i)));
    cards.forEach((c,i) => c.addEventListener('click', () => go(i)));

    document.addEventListener('keydown', e => {
      if (e.key === 'ArrowLeft')  go(currentIndex - 1);
      if (e.key === 'ArrowRight') go(currentIndex + 1);
    }, {passive:true});

    let x0 = 0;
    root.addEventListener('touchstart', e => x0 = e.changedTouches[0].screenX, {passive:true});
    root.addEventListener('touchend',   e => {
      const dx = x0 - e.changedTouches[0].screenX;
      if (Math.abs(dx) > 50) go(currentIndex + (dx>0 ? 1 : -1));
    }, {passive:true});
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, {once:true});
  } else {
    start();
  }
})();

(() => {
  const main = document.querySelector('main.fig');
  const toc  = main?.querySelector('.fig-toc');
  if (!main || !toc) return;

  const sections = Array.from(main.querySelectorAll('.fig-section[id]'));
  if (!sections.length) return;

  const links = Array.from(toc.querySelectorAll('.fig-toc__link'));
  const linkById = new Map(links.map(a => [a.getAttribute('href').slice(1), a]));

  function scrollWithOffset(target){
    const navH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 64;
    const y = target.getBoundingClientRect().top + window.scrollY - navH - 12;
    window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
  }
  links.forEach(a=>{
    a.addEventListener('click', e=>{
      e.preventDefault();
      const id  = a.getAttribute('href').slice(1);
      const sec = document.getElementById(id);
      if (sec) scrollWithOffset(sec);
    });
  });

  let activeId = sections[0].id;
  function setActive(id){
    if (id === activeId) return;
    const prev = linkById.get(activeId);
    prev?.classList.remove('is-active');
    prev?.removeAttribute('aria-current');

    const cur = linkById.get(id);
    cur?.classList.add('is-active');
    cur?.setAttribute('aria-current','true');

    activeId = id;
  }

  if ('IntersectionObserver' in window){
    const ratios = new Map(sections.map(s => [s.id, 0]));
    const io = new IntersectionObserver((entries)=>{
      entries.forEach(en=>{
        ratios.set(en.target.id, en.isIntersecting ? en.intersectionRatio : 0);
      });

      let bestId = activeId, bestR = 0;
      ratios.forEach((r,id)=>{ if (r > bestR){ bestR = r; bestId = id; } });
      if (bestR > 0){ setActive(bestId); return; }

      const navH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 64;
      const probeY = window.scrollY + navH + innerHeight * 0.35;
      let closestId = activeId, minD = Infinity;
      sections.forEach(sec=>{
        const d = Math.abs(sec.offsetTop - probeY);
        if (d < minD){ minD = d; closestId = sec.id; }
      });
      setActive(closestId);
    }, {
      root: null,
      rootMargin: '-30% 0px -50% 0px',
      threshold: Array.from({length:21},(_,i)=>i/20)
    });

    sections.forEach(sec => io.observe(sec));
    setActive(activeId);
  } else {

    function onScroll(){
      const navH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 64;
      const probeY = window.scrollY + navH + innerHeight * 0.35;
      let current = sections[0].id;
      sections.forEach(sec=>{ if (sec.offsetTop <= probeY) current = sec.id; });
      setActive(current);
    }
    document.addEventListener('scroll', onScroll, {passive:true});
    window.addEventListener('resize', onScroll, {passive:true});
    onScroll();
  }
})();

(() => {
  const hotmap = document.querySelector('.ori-map .hotmap');
  if (!hotmap) return;

  const dots = Array.from(hotmap.querySelectorAll('.dot'));
  const pop  = hotmap.querySelector('#mapPop');
  if (!pop) return;

  const tEl  = pop.querySelector('.map-pop__title');
  const pEl  = pop.querySelector('.map-pop__text');
  const btnX = pop.querySelector('.map-pop__close');

  let lastDot = null;

  function placePopOver(dot){
    const rDot = dot.getBoundingClientRect();
    const rMap = hotmap.getBoundingClientRect();
    const cx = rDot.left - rMap.left + rDot.width/2;
    const cy = rDot.top  - rMap.top  + rDot.height/2;

    pop.style.left = `${cx}px`;
    pop.style.top  = `${cy}px`;

    pop.hidden = false;
    pop.classList.remove('is-below');
    const needBelow = (cy - pop.offsetHeight - 18) < 0;
    pop.classList.toggle('is-below', needBelow);

    const pad = 10;
    const maxX = rMap.width - pad;
    const minX = pad;
    const clamped = Math.max(minX, Math.min(maxX, cx));
    pop.style.left = `${clamped}px`;

    lastDot = dot;
  }

  function openFrom(dot){
    tEl.textContent = dot.dataset.title || 'Nodo';
    pEl.textContent = dot.dataset.text  || '';
    placePopOver(dot);
  }

  function close(){ pop.hidden = true; lastDot = null; }

  dots.forEach(d=>{
    d.addEventListener('click', ()=> openFrom(d));
    d.addEventListener('keydown', e=>{
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openFrom(d); }
    });
  });

  btnX?.addEventListener('click', close);

  document.addEventListener('click', e=>{
    if (!pop.hidden && !pop.contains(e.target) && !dots.includes(e.target)) close();
  });

  window.addEventListener('resize', ()=>{
    if (!pop.hidden && lastDot) placePopOver(lastDot);
  });
})();
