const puppeteer = require('puppeteer-core');

(async () => {
    const browser = await puppeteer.launch({
        headless: true,
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    
    await page.setViewport({
        width: 412,
        height: 915,
        isMobile: true,
        hasTouch: true
    });
    
    console.log('Navigating to naivo.in/shop...');
    await page.goto('https://naivo.in/shop/', { waitUntil: 'networkidle2' });
    
    console.log('Clicking SHOW FILTERS...');
    const filterBtn = await page.$('.w-filter-opener');
    
    if (filterBtn) {
        await filterBtn.click();
        await new Promise(r => setTimeout(r, 600)); // Wait for transition
        console.log('Clicked show filters!');
    } else {
        console.log('SHOW FILTERS button not found!');
    }
    
    // Dump visible elements that might be the drawer
    const drawerInfo = await page.evaluate(() => {
        const elements = Array.from(document.querySelectorAll('div, section, aside'));
        const visibleDrawers = elements.filter(el => {
            const rect = el.getBoundingClientRect();
            const isVisible = rect.width > 0 && rect.height > 0;
            const style = window.getComputedStyle(el);
            const isFixedOrAbsolute = style.position === 'fixed' || style.position === 'absolute';
            const isTopOrHighZ = parseInt(style.zIndex, 10) > 100;
            return isVisible && isFixedOrAbsolute && isTopOrHighZ;
        }).map(el => {
            const rect = el.getBoundingClientRect();
            const style = window.getComputedStyle(el);
            return {
                tagName: el.tagName,
                className: el.className,
                id: el.id,
                position: style.position,
                top: style.top,
                left: style.left,
                right: style.right,
                bottom: style.bottom,
                width: style.width,
                height: style.height,
                zIndex: style.zIndex,
                rect: {
                    top: rect.top,
                    left: rect.left,
                    width: rect.width,
                    height: rect.height
                }
            };
        });
        
        return visibleDrawers;
    });
    
    console.log('Visible fixed/absolute overlays/drawers on naivo.in/shop:');
    console.log(JSON.stringify(drawerInfo, null, 2));
    
    await browser.close();
})();
