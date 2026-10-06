import React, { lazy, Suspense } from 'react';
import ReactDOM from 'react-dom/client';
import { MotionConfig } from 'motion/react';
import '@fontsource-variable/dm-sans';
import '@fontsource-variable/fraunces';
import '@fontsource-variable/fraunces/wght-italic.css';
import './styles.css';
import { initial } from './lib/api';
const PublicSite = lazy(() => import('./PublicSite'));
const Admin = lazy(() => import('./Admin'));
ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode><MotionConfig reducedMotion="user"><Suspense fallback={<div className="admin-loading">Preparando a casa…</div>}>{initial.admin ? <Admin/> : <PublicSite/>}</Suspense></MotionConfig></React.StrictMode>
);
