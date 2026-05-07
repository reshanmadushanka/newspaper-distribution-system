import Singlish from 'singlish-pro';
import { ref, onMounted, onUnmounted } from 'vue'

export const useSinglish = (options = {}) => {
  const singlish = ref(null);
 
  onMounted(() => {
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