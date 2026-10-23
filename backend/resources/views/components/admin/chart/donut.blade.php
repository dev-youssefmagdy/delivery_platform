@props(['chart', 'title', 'id' => null, 'height' => '18rem', 'for' => null, 'href' => null, 'hrefLabel' => null])
<x-admin.chart.frame type="donut" :chart="$chart" :title="$title" :id="$id" :height="$height" :for="$for" :href="$href" :href-label="$hrefLabel" {{ $attributes }} />
