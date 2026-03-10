import { chromium } from 'playwright';

const API = process.env.API_URL || 'http://127.0.0.1:8000/api/v1/auth/login';
const APP = process.env.APP_URL || 'http://localhost:5173';
const EMAIL = process.env.EMAIL || 'admin@example.com';
const PASSWORD = process.env.PASSWORD || 'secret';

async function main(){
  // obtain token from API
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: EMAIL, password: PASSWORD })
  });
  if (!res.ok) {
    console.error('Login failed', await res.text());
    process.exit(1);
  }
  const body = await res.json();
  const token = body.token;
  if (!token) { console.error('No token returned'); process.exit(1); }

  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  // inject token into localStorage before any scripts run
  await context.addInitScript({
    content: `window.localStorage.setItem('hf_token', ${JSON.stringify(token)});`
  });

  const page = await context.newPage();
  await page.goto(APP);
  console.log('Opened browser to', APP, 'with token set in localStorage');
}

main().catch(err => { console.error(err); process.exit(1); });
