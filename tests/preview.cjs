const {chromium}=require('playwright');
const fs=require('fs');const path=require('path');const assert=require('node:assert/strict');
const root=path.resolve(__dirname,'../preview');const base='https://mebsms21.github.io/Gorizont-web';
(async()=>{
 const browser=await chromium.launch({headless:true,...(process.env.CHROMIUM_PATH?{executablePath:process.env.CHROMIUM_PATH,args:['--no-sandbox']}:{})});
 const context=await browser.newContext({viewport:{width:1440,height:1000}});let missing=[],errors=[];
 await context.route(base+'/**',async route=>{
  const url=new URL(route.request().url());let name=decodeURIComponent(url.pathname.replace('/Gorizont-web/',''));
  let file=path.resolve(root,name);if(!file.startsWith(root+'/')&&file!==root)return route.abort();
  if(fs.existsSync(file)&&fs.statSync(file).isDirectory())file=path.join(file,'index.html');
  if(!fs.existsSync(file)){missing.push(name);return route.fulfill({status:404,body:'Missing'});}
  return route.fulfill({path:file});
 });
 const page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
 const walk=dir=>fs.readdirSync(dir,{withFileTypes:true}).flatMap(f=>f.isDirectory()?walk(path.join(dir,f.name)):f.name==='index.html'?[path.join(dir,f.name)]:[]);
 const pages=walk(root);for(const file of pages){const relative=path.relative(root,file).replace(/index.html$/,'');await page.goto(base+'/'+relative,{waitUntil:'networkidle'});assert.equal(await page.locator('h1').count(),1,relative);assert.equal(await page.locator('html').getAttribute('lang'),'ru-RU');assert.equal(await page.locator('script[src*="wp-includes"]').count(),0);}
 for(const width of [360,390,768,1024,1440,1920]){await page.setViewportSize({width,height:900});await page.goto(base+'/',{waitUntil:'networkidle'});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Overflow '+width);}
 await page.setViewportSize({width:1440,height:1000});await page.goto(base+'/');await page.waitForTimeout(2600);
 await page.locator('img').evaluateAll(es=>es.forEach(e=>e.loading='eager'));await page.waitForFunction(()=>[...document.images].every(i=>i.complete));
 for(let y=0;y<await page.evaluate(()=>document.body.scrollHeight);y+=700){await page.evaluate(y=>scrollTo(0,y),y);await page.waitForTimeout(150);}await page.evaluate(()=>scrollTo(0,0));await page.waitForTimeout(1100);
 fs.mkdirSync(path.resolve(__dirname,'../test-results'),{recursive:true});await page.screenshot({path:path.resolve(__dirname,'../test-results/preview-desktop.png'),fullPage:true});
 await page.locator('.wp-block-navigation__responsive-container-open').first().click();await page.waitForTimeout(1000);assert.equal(await page.locator('[role="dialog"]').count(),1);await page.keyboard.press('Escape');await page.waitForTimeout(550);assert.equal(await page.locator('.is-menu-open').count(),0);
 await page.setViewportSize({width:390,height:844});await page.screenshot({path:path.resolve(__dirname,'../test-results/preview-mobile.png'),fullPage:true});
 await page.goto(base+'/kontakty/');await page.locator('[name="name"]').fill('Проверка');await page.locator('[name="email"]').fill('test@example.com');await page.locator('[name="message"]').fill('Проверка скачивания брифа в статической версии.');const waiting=page.waitForEvent('download');await page.getByRole('button',{name:'Скачать бриф проекта'}).click();assert.equal((await waiting).suggestedFilename(),'Бриф-проекта.txt');
 assert.deepEqual(missing,[]);assert.deepEqual(errors,[]);console.log(JSON.stringify({pages:pages.length,widths:6,menu:true,brief:true,missing,errors}));await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
