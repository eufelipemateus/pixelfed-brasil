<template>
    <div class="landing-index-component">
        <section class="page-wrapper">
            <div class="sr-only" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;">
                <h1>Pixelfed Brasil - {{ config.domain }}</h1>
                <p>Instância brasileira do Pixelfed dedicada à fotografia ética e federada no Brasil.</p>
            </div>

            <div class="container container-compact">
                <div class="card bg-bluegray-900" style="border-radius: 10px;">
                    <div class="card-header bg-bluegray-800 nav-menu" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
                        <ul class="nav justify-content-around">
                            <li class="nav-item">
                                <router-link to="/" class="nav-link">{{ $t('site.about') }}</router-link>
                            </li>
                            <li v-if="config.show_directory" class="nav-item">
                                <router-link to="/web/directory" class="nav-link">{{ $t('site.directory') }}</router-link>
                            </li>
                            <li v-if="config.show_explore_feed" class="nav-item">
                                <router-link to="/web/explore" class="nav-link">{{ $t('site.explore') }}</router-link>
                            </li>
                        </ul>
                    </div>

                    <div class="card-img-top p-2">
                        <img
                            :src="config.about.banner_image"
                            class="img-fluid rounded"
                            style="width: 100%;max-height: 200px;object-fit: cover;"
                            alt="Server banner image"
                            height="200"
                            onerror="this.src='/storage/headers/default.jpg';this.onerror=null;">
                    </div>

                    <div class="card-body">
                        <div class="server-header text-center mb-4">
                            <h2 class="h3 text-white">Pixelfed Brasil</h2>
                            <p class="server-header-domain text-primary">{{ config.domain }}</p>
                            <p class="server-header-attribution">
                                <span class="badge badge-primary mb-2">Instância Brasileira</span><br>
                                <span v-html="$t('site.softwareDescription')"></span>
                            </p>
                        </div>

                        <div class="server-stats">
                            <div class="list-group">
                                <div class="list-group-item bg-transparent">
                                    <p class="stat-value">{{ formatCount(config.stats.posts_count) }}</p>
                                    <p class="stat-label">{{ $t('site.posts') }}</p>
                                </div>
                                <div class="list-group-item bg-transparent">
                                    <p class="stat-value">{{ formatCount(config.stats.active_users) }}</p>
                                    <p class="stat-label">{{ $t('site.activeUsers') }}</p>
                                </div>
                                <div class="list-group-item bg-transparent">
                                    <p class="stat-value">{{ formatCount(config.stats.total_users) }}</p>
                                    <p class="stat-label">{{ $t('site.totalUsers') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="server-admin">
                             </div>

                        <div class="accordion" id="accordion">
                          <div class="card bg-bluegray-700">
                            <div class="card-header bg-bluegray-800" id="headingOne">
                              <h2 class="mb-0">
                                <button class="btn btn-link btn-block" type="button" data-toggle="collapse" data-target="#collapseOne" aria-controls="collapseOne" @click="toggleAccordion(0)">
                                    <span class="text-white h5">
                                        <i class="far fa-info-circle mr-2 text-muted"></i>
                                        Sobre o Pixelfed Brasil
                                    </span>
                                    <i class="far" :class="[ accordionTab === 0 ? 'fa-chevron-left text-primary': 'fa-chevron-down']"></i>
                                </button>
                              </h2>
                            </div>

                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                              <div class="card-body about-text">
                                <p>Comunidade voltada para usuários brasileiros que buscam privacidade e federação.</p>
                                <hr class="border-secondary">
                                <p v-html="config.about.description"></p>
                              </div>
                            </div>
                          </div>
                          </div>
                    </div>
                </div>
            </div>
            <footer-component />
        </section>
    </div>
</template>
<script type="text/javascript">
	export default {
		data() {
			return {
				config: window.pfl,
				accordionTab: undefined,

			}
		},

		methods: {
			toggleAccordion(idx) {
				if(this.accordionTab == idx) {
					this.accordionTab = undefined;
					return;
				}
				this.accordionTab = idx;
			},

			formatCount(val) {
				if(!val) {
					return 0;
				}
                const userLocale = navigator.language || 'en-CA';
				return val.toLocaleString(userLocale, { compactDisplay: "short", notation: "compact"});
			},

			formatBytes(bytes, unit = 'megabyte') {
				const units = ['byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte'];
				const navigatorLocal = navigator.languages && navigator.languages.length >= 0 ? navigator.languages[0] : 'en-US';
				const unitIndex = Math.max(0, Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1));
				return Intl.NumberFormat(navigatorLocal, {
				    style: 'unit',
					unit : units[unitIndex],
					useGrouping: false,
					maximumFractionDigits: 0,
					roundingMode: 'ceil'
				}).format(bytes / (1024 ** unitIndex))
			}
		}
	}
</script>
