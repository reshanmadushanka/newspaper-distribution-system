import Singlish from 'singlish-pro';

export const useSinglish = (options = {}) => {
    const singlish = ref(null);

    onMounted(() => {
        // Use universal browser check
        if (typeof window !== 'undefined') {
            singlish.value = new Singlish({
                showUI: true,
                enabled: false,
                ...options
            });
        }
    });

    onUnmounted(() => {
        if (singlish.value) {
            singlish.value.destroy();
        }
    });

    return singlish;
};