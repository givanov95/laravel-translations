import { App } from 'vue';

export type Translations = Record<string, string>;

export default {
    install: (app: App, translations: Translations) => {
        app.config.globalProperties.__ = (
            key: string,
            replacements: Record<string, string | number> = {}
        ): string => {
            let translation = translations[key] || key;

            for (const placeholder of Object.keys(replacements)) {
                translation = translation.replace(`:${placeholder}`, String(replacements[placeholder]));
            }

            return translation;
        };
    },
};
