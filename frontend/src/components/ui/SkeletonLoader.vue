<template>
  <!-- Loading Skeleton -->
  <div v-if="loading" class="space-y-4">
    <!-- Header Skeleton -->
    <div class="h-8 bg-slate-200 rounded-lg animate-pulse w-1/3" />
    
    <!-- Table Skeleton -->
    <div class="border border-slate-200 rounded-lg overflow-hidden">
      <!-- Header Row -->
      <div class="flex items-center bg-slate-50 p-4 gap-4 border-b border-slate-200">
        <div class="h-6 bg-slate-200 rounded w-12 flex-shrink-0 animate-pulse" />
        <div class="h-6 bg-slate-200 rounded flex-1 animate-pulse" />
        <div class="h-6 bg-slate-200 rounded flex-1 animate-pulse" />
        <div class="h-6 bg-slate-200 rounded w-20 flex-shrink-0 animate-pulse" />
      </div>
      
      <!-- Body Rows -->
      <template v-for="i in rowCount" :key="i">
        <div class="flex items-center p-4 gap-4 border-b border-slate-200 last:border-b-0 hover:bg-slate-50">
          <div class="h-5 bg-slate-200 rounded w-12 flex-shrink-0 animate-pulse" style="animation-delay: `${i * 50}ms`" />
          <div class="h-5 bg-slate-200 rounded flex-1 animate-pulse" style="animation-delay: `${i * 50}ms`" />
          <div class="h-5 bg-slate-200 rounded flex-1 animate-pulse" style="animation-delay: `${i * 50}ms`" />
          <div class="flex gap-2 w-20 flex-shrink-0">
            <div class="h-5 bg-slate-200 rounded flex-1 animate-pulse" style="animation-delay: `${i * 50}ms`" />
            <div class="h-5 bg-slate-200 rounded flex-1 animate-pulse" style="animation-delay: `${i * 50}ms`" />
          </div>
        </div>
      </template>
    </div>
    
    <!-- Pagination Skeleton -->
    <div class="flex items-center justify-between pt-4">
      <div class="h-5 bg-slate-200 rounded w-40 animate-pulse" />
      <div class="flex gap-2">
        <div class="h-9 w-9 bg-slate-200 rounded-lg animate-pulse" />
        <div class="h-9 w-9 bg-slate-200 rounded-lg animate-pulse" />
        <div class="h-9 w-9 bg-slate-200 rounded-lg animate-pulse" />
      </div>
    </div>
  </div>
  
  <!-- Actual Content (when not loading) -->
  <slot v-else />
</template>

<script>
export default {
  name: 'SkeletonLoader',
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    rowCount: {
      type: Number,
      default: 5,
    },
  },
}
</script>

<style scoped>
@keyframes shimmer {
  0% {
    background: linear-gradient(90deg, #e2e8f0 0%, #f1f5f9 50%, #e2e8f0 100%);
    background-size: 200% 100%;
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.animate-pulse {
  animation: shimmer 2s infinite;
  background: linear-gradient(90deg, #e2e8f0 0%, #f1f5f9 50%, #e2e8f0 100%);
  background-size: 200% 100%;
}
</style>
