import { usePage } from '@inertiajs/vue3'

/**
 * Composable for accessing translations in Vue components.
 *
 * Usage:
 * const { t } = useTranslation()
 * t('common.save') => Returns translated string
 * t('newspapers.create') => Returns translated string
 * t('common.welcome', { name: 'John' }) => Returns translated string with replacements
 */
export function useTranslation() {
    const page = usePage()

    /**
     * Translate a key with optional replacements.
     *
     * @param {string} key - Dot notation key (e.g., 'common.save', 'newspapers.create')
     * @param {Object} replacements - Optional key-value pairs for string replacement
     * @returns {string} Translated string or original key if not found
     */
    const t = (key, replacements = {}) => {
        const keys = key.split('.')
        let value = page.props.translations

        // Navigate through nested translation keys
        for (const k of keys) {
            value = value?.[k]
            if (value === undefined || value === null) {
                return key // Return original key if translation not found
            }
        }

        // Handle replacements like :name, :email, etc.
        if (Object.keys(replacements).length > 0) {
            return Object.entries(replacements).reduce(
                (str, [replacementKey, val]) => str.replace(`:${replacementKey}`, val),
                value
            )
        }

        return value
    }

    return { t }
}
