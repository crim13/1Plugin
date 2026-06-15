const path = require('path');

module.exports = {
  entry: './src/index.jsx',
  externals: {
    react: 'React',
  },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', { modules: false, targets: '> 5%' }],
              '@babel/preset-react',
            ],
          },
        },
      },
    ],
  },
  resolve: {
    extensions: ['.js', '.jsx'],
  },
  output: {
    filename: 'oneplugin-divi5-faq.js',
    path: path.resolve(__dirname, 'build'),
  },
};
