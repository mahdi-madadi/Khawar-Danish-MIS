/* Store only the appearance preference, never student or account information. */
(()=>{
 const root=document.documentElement,media=window.matchMedia('(prefers-color-scheme: dark)');let preference=null;
 try{const saved=localStorage.getItem('kd-theme');if(saved==='light'||saved==='dark')preference=saved;}catch{}
 const apply=theme=>{root.dataset.theme=theme;document.querySelectorAll('[data-theme-toggle]').forEach(button=>{
  const label=theme==='dark'?button.dataset.lightLabel:button.dataset.darkLabel;
  button.setAttribute('aria-pressed',String(theme==='dark'));button.setAttribute('aria-label',label);button.title=label;
  const text=button.querySelector('[data-theme-label]');if(text)text.textContent=label;
 });};
 apply(preference||(media.matches?'dark':'light'));
 document.addEventListener('DOMContentLoaded',()=>{
  apply(root.dataset.theme);document.querySelectorAll('[data-theme-toggle]').forEach(button=>button.addEventListener('click',()=>{
   preference=root.dataset.theme==='dark'?'light':'dark';try{localStorage.setItem('kd-theme',preference);}catch{}apply(preference);
  }));
 });
 media.addEventListener('change',()=>{if(!preference)apply(media.matches?'dark':'light');});
 window.addEventListener('storage',event=>{if(event.key==='kd-theme'){preference=['light','dark'].includes(event.newValue)?event.newValue:null;apply(preference||(media.matches?'dark':'light'));}});
})();
