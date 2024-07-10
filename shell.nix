{ pkgs ? import <nixpkgs> {} }:

# SPDX-FileCopyrightText: Copyright (c) 2022, MetaProvide Holding EKF
# SPDX-License-Identifier: AGPL-3.0-or-later

pkgs.mkShell {
  name = "nextcloud";
  nativeBuildInputs = with pkgs; with pkgs.nodePackages;
    [ nodejs-18_x
      php80
      php80Packages.composer
      stylelint
      eslint
      prettier
	  webpack-cli
    ];
}
