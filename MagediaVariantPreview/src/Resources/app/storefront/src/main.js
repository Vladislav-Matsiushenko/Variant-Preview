import MagediaVariantPreview from './magedia-variant-preview/magedia-variant-preview.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('MagediaVariantPreview', MagediaVariantPreview, '[data-magedia-variant-preview]');