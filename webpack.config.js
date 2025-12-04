const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const fs = require('fs-extra');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      // Main bundle - always loaded
      main: [
        './src/scss/main.scss',
        './src/js/main.js'
      ],
      // Cart enhancements - loaded on cart/checkout pages
      'cart-enhancements': [
        './src/scss/components/_cart-enhancements.scss',
        './js/cart-enhancements.js'
      ],
      // Service state manager - shared utility
      'service-state-manager': './js/service-state-manager.js',
      // Cart service recommendations - loaded on mini cart
      'cart-service-recommendations': [
        './src/scss/components/_cart-service-recommendations.scss',
        './js/cart-service-recommendations.js'
      ],
      // Product service recommendations - loaded on product pages
      'product-service-recommendations': [
        './src/scss/components/_product-service-recommendations.scss',
        './js/product-service-recommendations.js'
      ],
      // Side drawer - loaded when needed
      'cart-side-drawer': [
        './src/scss/components/_cart-side-drawer.scss',
        './js/cart-side-drawer.js'
      ]
    },
    output: {
      path: path.resolve(__dirname, 'impreza-child-dist/assets'),
      filename: 'js/[name].min.js',
      clean: true
    },
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env']
            }
          }
        },
        {
          test: /\.scss$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                sourceMap: !isProduction
              }
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: !isProduction
              }
            }
          ]
        }
      ]
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: 'css/[name].min.css'
      }),
      {
        apply: (compiler) => {
          compiler.hooks.afterEmit.tap('CopyThemeFiles', () => {
            if (isProduction) {
              const distDir = path.resolve(__dirname, 'impreza-child-dist');
              
              // Copy PHP files
              fs.copySync('./functions.php', path.join(distDir, 'functions.php'));
              fs.copySync('./style.css', path.join(distDir, 'style.css'));
              
              // Copy includes directory
              if (fs.existsSync('./includes')) {
                fs.copySync('./includes', path.join(distDir, 'includes'));
              }
              
              // Copy screenshot if exists (check both .png and .jpg)
              if (fs.existsSync('./screenshot.png')) {
                fs.copySync('./screenshot.png', path.join(distDir, 'screenshot.png'));
              } else if (fs.existsSync('./screenshot.jpg')) {
                fs.copySync('./screenshot.jpg', path.join(distDir, 'screenshot.jpg'));
              }
              
              // Copy README.md only
              if (fs.existsSync('./README.md')) {
                fs.copySync('./README.md', path.join(distDir, 'README.md'));
              }
              
              // Update functions.php paths to use 'assets' folder
              const functionsPath = path.join(distDir, 'functions.php');
              let functionsContent = fs.readFileSync(functionsPath, 'utf8');
              
              // Replace /dist/ with /assets/ in all paths
              functionsContent = functionsContent.replace(/\/dist\//g, '/assets/');
              
              fs.writeFileSync(functionsPath, functionsContent);
              
              console.log('\n✅ Production theme exported to: impreza-child-dist/\n');
            }
          });
        }
      }
    ],
    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            format: {
              comments: false,
            },
            compress: {
              drop_console: isProduction
            }
          },
          extractComments: false,
        }),
        new CssMinimizerPlugin()
      ]
    },
    devtool: isProduction ? false : 'source-map',
    performance: {
      hints: isProduction ? 'warning' : false,
      maxEntrypointSize: 512000,
      maxAssetSize: 512000
    }
  };
};
