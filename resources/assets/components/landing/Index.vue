<template>
    <div class="landing-index-component custom-modern-theme">
        <section class="page-wrapper py-5">
            <div class="sr-only">
                <h1>Pixelfed Brasil - {{ config.domain }}</h1>
                <p>Instância brasileira do Pixelfed dedicada à fotografia ética e federada no Brasil.</p>
            </div>

            <div class="container container-compact">
                <div class="main-glass-card shadow-lg">

                    <div class="nav-menu-modern p-3">
                        <ul class="nav justify-content-center">
                            <li class="nav-item">
                                <router-link to="/" class="nav-link text-white active-link">{{ $t('site.about') }}</router-link>
                            </li>
                            <li v-if="config.show_explore_feed" class="nav-item">
                                <router-link to="/web/explore" class="nav-link text-white">{{ $t('site.explore')
                                    }}</router-link>
                            </li>
                            <li v-if="config.show_directory" class="nav-item">
                                <router-link to="/web/directory" class="nav-link text-white">{{ $t('site.directory')
                                    }}</router-link>
                            </li>

                        </ul>
                    </div>

                    <div class="position-relative overflow-hidden">
                        <div class="banner-overlay"></div>
                        <img :src="config.about.banner_image" class="server-banner" alt="Pixelfed Brasil Banner"
                            onerror="this.src='/storage/headers/default.jpg';this.onerror=null;">

                        <div class="floating-badge">
                            <span class="pulse-icon"></span>
                            Instância Brasileira
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-5">
                            <h2 class="display-5 font-weight-bold text-white mb-1">Pixelfed Brasil</h2>
                            <p class="domain-gradient font-weight-bold mb-3">{{ config.domain }}</p>
                            <div class="mx-auto separator-glow"></div>
                            <p class="server-header-attribution mt-4 text-muted">
                                <span v-html="$t('site.softwareDescription')"></span>
                            </p>
                        </div>

                        <div class="stats-modern-grid mb-5">
                            <div class="stat-box">
                                <span class="stat-num text-white">{{ formatCount(config.stats.posts_count) }}</span>
                                <span class="stat-desc">{{ $t('site.posts') }}</span>
                            </div>
                            <div class="stat-box highlighted">
                                <span class="stat-num text-white">{{ formatCount(config.stats.active_users) }}</span>
                                <span class="stat-desc">{{ $t('site.activeUsers') }}</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-num text-white">{{ formatCount(config.stats.total_users) }}</span>
                                <span class="stat-desc">{{ $t('site.totalUsers') }}</span>
                            </div>
                        </div>

                        <div class="admin-grid-modern p-4 rounded-lg mb-5 border border-dark">
                            <div v-if="config.contact.account" class="admin-info">
                                <span class="label-small text-uppercase text-muted" style="font-size: 0.7rem;">{{
                                    $t('site.managedBy') }}</span>
                                <a :href="config.contact.account.url"
                                    class="d-flex align-items-center mt-2 text-decoration-none" target="_blank">
                                    <img :src="config.contact.account.avatar" class="avatar-modern" alt="Admin Avatar"
                                        onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;">
                                    <div class="ml-3">
                                        <p class="mb-0 text-white font-weight-bold">{{
                                            config.contact.account.display_name }}</p>
                                        <p class="mb-0 text-primary small">&commat;{{ config.contact.account.username }}
                                        </p>
                                    </div>
                                </a>
                            </div>
                            <div v-if="config.contact.email" class="contact-info mt-3 mt-md-0">
                                <span class="label-small text-uppercase text-muted d-block"
                                    style="font-size: 0.7rem;">{{ $t('site.contact') }}</span>
                                <a :href="`mailto:${config.contact.email}`" class="text-white-50 small">{{
                                    config.contact.email }}</a>
                            </div>
                        </div>

                        <div class="custom-accordion">
                            <div class="accordion-item mb-3">
                                <button class="accordion-trigger" @click="toggleAccordion(0)"
                                    :class="{ active: accordionTab === 0 }">
                                    <span><i class="far fa-info-circle mr-2 text-primary"></i> Sobre o Pixelfed
                                        Brasil</span>
                                    <i class="far"
                                        :class="[accordionTab === 0 ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                </button>
                                <div class="accordion-content p-4" v-show="accordionTab === 0">
                                    <p class="text-white-50">Comunidade voltada para usuários brasileiros que buscam
                                        privacidade e federação.</p>
                                    <hr class="border-secondary my-3">
                                    <div class="text-white-50" v-html="config.about.description"></div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <button class="accordion-trigger" @click="toggleAccordion(1)"
                                    :class="{ active: accordionTab === 1 }">
                                    <span><i class="far fa-list mr-2 text-primary"></i> {{ $t('site.serverRules')
                                        }}</span>
                                    <i class="far"
                                        :class="[accordionTab === 1 ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                </button>
                                <div class="accordion-content p-4" v-show="accordionTab === 1">
                                    <div class="list-group list-group-flush bg-transparent">
                                        <div v-for="rule in config.rules" :key="rule.id"
                                            class="list-group-item bg-transparent border-secondary px-0">
                                            <span class="badge badge-primary mr-2">{{ rule.id }}</span>
                                            <span class="text-white-50">{{ rule.text }}</span>
                                        </div>
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
            accordionTab: 0, // Inicia aberto no 'Sobre'
        }
    },
    methods: {
        toggleAccordion(idx) {
            this.accordionTab = (this.accordionTab === idx) ? undefined : idx;
        },
        formatCount(val) {
            if (!val) return 0;
            return val.toLocaleString(navigator.language || 'pt-BR', { compactDisplay: "short", notation: "compact" });
        }
    }
}
</script>

<style scoped>
/* Aqui você mantém os estilos que já possui no seu <style scoped> anterior */
.custom-modern-theme {
    background: radial-gradient(circle at top right, #1a202c, #0d1117);
    min-height: 100vh;
    color: #e2e8f0;
}

.main-glass-card {
    background: rgba(23, 27, 34, 0.8);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    overflow: hidden;
}

.nav-menu-modern {
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
/* Ajuste da Nav Link ativa */
.active-link {
    color: #10c5f8 !important;
    border-bottom: 2px solid #10c5f8;
    text-shadow: 0 0 10px rgba(16, 197, 248, 0.5);
}
.server-banner {
    width: 100%;
    height: 240px;
    object-fit: cover;
}

.banner-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent, rgba(13, 17, 23, 0.9));
    z-index: 1;
}

.floating-badge {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 2;
    background: rgba(16, 197, 248, 0.15);
    border: 1px solid rgba(16, 197, 248, 0.5);
    backdrop-filter: blur(4px);
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: bold;
    color: #fff;
    display: flex;
    align-items: center;
}

.pulse-icon {
    width: 8px;
    height: 8px;
    background: #10c5f8;
    border-radius: 50%;
    margin-right: 8px;
    box-shadow: 0 0 10px #10c5f8;
    animation: pulse 2s infinite;
}

.domain-gradient {
    font-size: 1.4rem;
    background: linear-gradient(90deg, #10c5f8, #6366f1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.separator-glow {
    width: 50px;
    height: 3px;
    background: #10c5f8;
    border-radius: 2px;
    box-shadow: 0 0 10px rgba(16, 197, 248, 0.5);
}

.stats-modern-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.stat-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 15px;
}

.stat-num {
    font-size: 1.5rem;
    font-weight: 800;
}

.stat-desc {
    font-size: 0.7rem;
    color: #718096;
    text-transform: uppercase;
}

.stat-box.highlighted {
    border: 1px solid rgba(16, 197, 248, 0.3);
    background: rgba(16, 197, 248, 0.05);
}

.accordion-trigger {
    width: 100%;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    padding: 15px 20px;
    border-radius: 12px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: 0.3s;
}

.accordion-trigger.active {
    background: rgba(16, 197, 248, 0.1);
    border-color: rgba(16, 197, 248, 0.4);
}

.avatar-modern {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    object-fit: cover;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }

    50% {
        transform: scale(1.4);
        opacity: 0.5;
    }

    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@media (max-width: 576px) {
    .stats-modern-grid {
        grid-template-columns: 1fr;
    }
}
</style>
