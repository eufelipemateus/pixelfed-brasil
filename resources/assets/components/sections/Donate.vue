<template>
    <div class="notifications-component">
        <div class="card shadow-sm mb-3" style="overflow: hidden;border-radius: 15px !important;">
			<div class="card-body">
                <div class="max-w-md mx-auto p-6 text-center shadow-xl rounded-2xl bg-white">
                    <h3 class="text-lg font-semibold">Apoie o Pixelfed Brasil!</h3>
                    <p class="text-gray-600 mb-4">
                        Sua contribuição ajuda a manter a plataforma. Faça sua doação e fortaleça nossa comunidade!
                    </p>
                   <form action="https://www.paypal.com/donate" method="post" target="_blank">
                    <input type="hidden" name="business" value="J7HKMWTQL7E8L" />
                    <input type="hidden" name="no_recurring" value="0" />
                    <input type="hidden" name="item_name" value="Contribua para o crescimento do Pixelfed Brasil!
                    " />
                    <input type="hidden" name="currency_code" value="BRL" />
                    <input type="image" src="https://www.paypalobjects.com/pt_BR/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Faça doações com o botão do PayPal" />
                    <img alt="" border="0" src="https://www.paypal.com/pt_BR/i/scr/pixel.gif" width="1" height="1" />
                    </form>

                </div>
            </div>
		</div>
    </div>
</template>

<script>
export default {
  name: "Donate"
};
</script>

<style lang="scss">
	.notifications-component {
		&-feed {
			min-height: 50px;
			max-height: 300px;
			overflow-y: auto;

			-ms-overflow-style: none;
			scrollbar-width: none;
			overflow-y: scroll;

			&::-webkit-scrollbar {
				display: none;
			}

		}
		.card {
			width: 100%;
			position: relative;
		}

		.card-body {
			width: 100%;
		}
	}
</style>
