'use strict';
const fs=require('fs'),{spawn}=require('child_process'),{chromium}=require('playwright');
const axeSource=fs.readFileSync(require.resolve('axe-core/axe.min.js'),'utf8');
const host='127.0.0.1',port='8898',baseURL=`http://${host}:${port}`;
const routes=['/','/markets','/coins/bitcoin','/exchanges','/ton','/screener','/premium','/support','/watchlist'];
const server=spawn('php',['-S',`${host}:${port}`,'dev/php/router.php'],{cwd:process.cwd(),env:{...process.env,TONBANKCARD_PROFILE:'local',TONBANKCARD_BASE_URL:`${baseURL}/`,TONBANKCARD_LOCAL_BASE_URL:`${baseURL}/`,TONBANKCARD_CDN:'false'},stdio:['ignore','ignore','ignore']});
(async()=>{
  for(let i=0;i<60;i++){try{const r=await fetch(baseURL+'/');if(r.ok)break;}catch(e){}await new Promise(r=>setTimeout(r,250));}
  const b=await chromium.launch();const p=await b.newPage({viewport:{width:1280,height:900}});
  const agg={};
  for(const route of routes){
    const resp=await p.goto(baseURL+route,{waitUntil:'domcontentloaded'});
    if(!resp||!resp.ok()){console.log('ROUTE FAIL',route,resp&&resp.status());continue;}
    await p.waitForTimeout(1400);await p.addScriptTag({content:axeSource});
    const res=await p.evaluate(async()=>await window.axe.run(document,{resultTypes:['violations'],runOnly:{type:'rule',values:['color-contrast']}}));
    for(const v of res.violations)for(const n of v.nodes){const d=(n.any[0]||{}).data||{};const k=`fg=${d.fgColor} bg=${d.bgColor} ratio=${d.contrastRatio}`;(agg[k]=agg[k]||{count:0,ex:[],routes:new Set()});agg[k].count++;agg[k].routes.add(route);if(agg[k].ex.length<3)agg[k].ex.push(n.target.join(' '));}
  }
  for(const k of Object.keys(agg).sort()){const a=agg[k];console.log(`${k} | n=${a.count} routes=${[...a.routes].length}`);a.ex.forEach(e=>console.log('    '+e));}
  await b.close();server.kill('SIGTERM');
})();
