<template>
	<div class="footer-component">
		<div class="footer-component-links">
			<a href="/help">{{ $t('navmenu.help') }}</a>
			<div class="spacer">·</div>
			<a href="/terms">{{ $t('navmenu.terms') }}</a>
			<div class="spacer">·</div>
			<a href="/privacy">{{ $t('navmenu.privacy') }}</a>
            <div class="spacer">·</div>
            <a href="https://www.paypal.com/donate/?business=J7HKMWTQL7E8L&no_recurring=0&item_name=Contribua+para+o+crescimento+do+Pixelfed+Brasil%21&currency_code=BRL" target="_blank" >Doar</a>
		</div>

		<div class="footer-component-attribution">
			<div><span>© {{ getYear() }} {{config.domain}}</span></div>
			<div class="spacer">·</div>
			<div>Mantido por <a href="https://felipemateus.com" class="text-bluegray-500 font-weight-bold">Felipe Mateus</a></div>
			<div class="spacer">·</div>
			<div><span>v{{config.version}}</span></div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				config: window.pfl
			}
		},

		methods: {
			getYear() {
				return (new Date().getFullYear());
			}
		}
	}
</script>
