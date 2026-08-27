import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.sigcaz.dashboard',
  appName: 'SIGCAZ Dashboard',
  webDir: 'dist/sigcaz-frontend/browser',
  server: {
    androidScheme: 'https',
    allowNavigation: ['*'],
  },
};

export default config;