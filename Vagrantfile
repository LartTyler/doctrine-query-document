# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-19.04"

  config.vm.provision "bootstrap", type: "shell", inline: <<-SHELL
  	apt-get update -y
  	add-apt-repository -y ppa:ondrej/php

  	apt-get install -y php7.3-common php7.3-cli php7.3-curl php7.3-zip php7.3-mbstring php7.3-xml php7.3-json
  	apt-get install -y composer
  SHELL
end
