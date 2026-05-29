'use strict';
const {spawn}=require('child_process'),{chromium}=require('playwright');
const host='127.0.0.1',port='8897',baseURL=`http://${host}:${port}`;
const out=process.argv[2]||'experiments/shot.png';
const server=spawn('php',['-S',`${host}:${port}`,'dev/php/router.php'],{cwd:process.cwd(),env:{...process.env,TONBANKCARD_PROFILE:'local',TONBANKCARD_BASE_URL:`${baseURL}/`,TONBANKCARD_LOCAL_BASE_URL:`${baseURL}/`,TONBANKCARD_CDN:'false'},stdio:['ignore','ignore','ignore']});
(async()=>{
  for(let i=0;i<60;i++){try{const r=await fetch(baseURL+'/');if(r.ok)break;}catch(e){}await new Promise(r=>setTimeout(r,250));}
  const b=await chromium.launch();const p=await b.newPage({viewport:{width:1280,height:800}});
  await p.goto(baseURL+'/',{waitUntil:'domcontentloaded'});await p.waitForTimeout(1800);
  await p.screenshot({path:out,clip:{x:0,y:0,width:1280,height:120}});
  const hdr=await p.evaluate(()=>{const el=document.querySelector('.tbc-app-bar');const cs=getComputedStyle(el);return {bg:cs.backgroundColor};});
  console.log('header bg:',JSON.stringify(hdr));
  await b.close();server.kill('SIGTERM');
})();
