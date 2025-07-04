<template>
	<div class="read-more-component" style="word-break: break-word;">
		<div v-html="content"></div>

        <a v-if="canTranslate"  href="#" @click.prevent="translate" >{{$t('common.translate')}} </a>
		<!-- <div v-if="status.content.length < 200" v-html="content"></div>
		<div v-else>
			<span v-html="content"></span>
			<a
				v-if="cursor == 200 || fullContent.length > cursor"
				class="font-weight-bold text-muted" href="#"
				style="display: block;white-space: nowrap;"
				@click.prevent="readMore">
				<i class="d-none fas fa-caret-down"></i> {{ $t('common.readMore') }}...
			</a>
		</div> -->
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			status: {
				type: Object
			},

            noAutoLink:{
                type: Boolean,
            },

			cursorLimit: {
				type: Number,
				default: 200
			}
		},

		data() {
			return {
				preRender: undefined,
				fullContent: null,
				content: null,
				cursor: 200
			}
		},

		mounted() {
			this.rewriteLinks();
		},
        computed: {
            canTranslate() {
                return window._sharedData.can_translate;
            }
        },
		methods: {
			readMore() {
				this.cursor = this.cursor + 200;
				this.content = this.fullContent.substr(0, this.cursor);
			},

			rewriteLinks() {
				let content = this.status.content;
				let el = document.createElement('div');
				el.innerHTML = content;
				el.querySelectorAll('a[class*="hashtag"]')
				.forEach(elr => {
					let tag = elr.innerText;
					if(tag.substr(0, 1) == '#') {
						tag = tag.substr(1);
					}
					elr.removeAttribute('target');
					elr.setAttribute('href', '/i/web/hashtag/' + tag);
				})
				el.querySelectorAll('a:not(.hashtag)[class*="mention"], a:not(.hashtag)[class*="list-slug"]')
				.forEach(elr => {
					let name = elr.innerText;
					if(name.substr(0, 1) == '@') {
						name = name.substr(1);
					}
					if(this.status.account.local == false && !name.includes('@')) {
						let domain = document.createElement('a');
						domain.href = elr.getAttribute('href');
						name = name + '@' + domain.hostname;
					}
					elr.removeAttribute('target');
					elr.setAttribute('href', '/i/web/username/' + name);
				})

                if(this.noAutoLink){
                    el.querySelectorAll('a')
                    .forEach(link => {
                        const classes = link.className || '';
                        const isHashtag = classes.includes('hashtag');
                        const isMention = classes.includes('mention') || classes.includes('list-slug');
                        if (!isHashtag && !isMention) {
                            const span = document.createElement('span');
                            span.innerHTML = link.innerHTML;
                            link.replaceWith(span);
                        }
                    });
                }

				this.content = el.outerHTML;
				this.injectCustomEmoji();
			},

			injectCustomEmoji() {
				// console.log('inecting custom emoji');
				// let re = /:\w{1,100}:/g;
				// let matches = this.status.content.match(re);
				// console.log(matches);
				// if(this.status.emojis.length == 0) {
				// 	return;
				// }
				let self = this;
				this.status.emojis.forEach(function(emoji) {
					let img = `<img draggable="false" class="emojione custom-emoji" alt="${emoji.shortcode}" title="${emoji.shortcode}" src="${emoji.url}" data-original="${emoji.url}" data-static="${emoji.static_url}" width="18" height="18" onerror="this.onerror=null;this.src='/storage/emoji/missing.png';" />`;
					self.content = self.content.replace(`:${emoji.shortcode}:`, img);
				});
				// this.content = this.content.replace(':fediverse:', '😅');
			},
            async translate() {
                if (!this.status || !this.status.id) {
                    console.warn('status is undefined or missing ID');
                    return;
                }

                try {
                    const response = await axios.get(`/api/v1/statuses/${this.status.id}/translate`);
                    this.status.content = response.data.text;
                    this.rewriteLinks();
                } catch (error) {
                    console.log(error);
               }
            }
		}
	}
</script>
