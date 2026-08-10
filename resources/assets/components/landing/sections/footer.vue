<template>
	<div class="footer-component">
		<div class="footer-component-links">
            <a href="/about">{{ $t('navmenu.about') }}</a>
            <div class="spacer">·</div>
			<a href="/help">{{ $t('navmenu.help') }}</a>
			<div class="spacer">·</div>
			<a href="/terms">{{ $t('navmenu.terms') }}</a>
			<div class="spacer">·</div>
			<a href="/privacy">{{ $t('navmenu.privacy') }}</a>
            <div class="spacer">·</div>
            <a href="/app">App</a>
            <div class="spacer">·</div>
            <a v-if="config.show_legal_notice_link" href="/legal-notice">Legal Notice</a>
            <div v-if="config.show_legal_notice_link" class="spacer">·</div>
			<a href="/donate" style="background: #ff6600; color: #fff; border-radius: 20px; font-weight: bold; padding: 6px 18px;" >{{ $t('navmenu.donate') }}</a>
		</div>

		<div class="footer-component-attribution">
			<div><span>© {{ getYear() }} {{config.domain}}</span></div>
            <div class="spacer">·</div>
            {{totalUsers }} Usuarios Online
			<div class="spacer">·</div>
			<div>Mantido por <a href="https://felipemateus.com/eufelipemateus" class="text-bluegray-500 font-weight-bold">Felipe Mateus</a></div>
			<div class="spacer">·</div>
			<div><span>v{{config.version}}</span></div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				config: window.pfl,
                totalUsers: window.online_users
			}
		},

    methods: {
        getYear() {
            return (new Date().getFullYear());
        }
    }
}
</script>
