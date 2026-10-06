import { defineConfig } from '@playwright/test';
import { existsSync } from 'node:fs';
const cloudBrowser='/workspace/tooling/browser/chromium-1234/chrome-linux64/chrome';
export default defineConfig({
  testDir:'./tests/browser',
  fullyParallel:false,
  workers:1,
  timeout:45000,
  retries:0,
  reporter:'list',
  use:{
    baseURL:'http://127.0.0.1:8081',
    viewport:{width:1440,height:1000},
    trace:'retain-on-failure',
    screenshot:'only-on-failure',
    launchOptions:{executablePath:process.env.CHROME_PATH || (existsSync(cloudBrowser) ? cloudBrowser : undefined),args:['--no-sandbox']},
  },
  webServer:{command:'python3 tests/server.py --port 8081',url:'http://127.0.0.1:8081/api/index.php?action=public',reuseExistingServer:false,timeout:30000,stdout:'ignore',stderr:'ignore'},
});
