#include "../nano/nano"
DEFINE_NANO_MAIN

class math : public nano::service
{
public:
	QString shortName() const override { return "math"; }
	QString longName() const override { return "math lib"; }
	QString version() const override { return "1.0.1"; }
	QString description() const override { return "simple math lib"; }
	std::vector<nano::method> methods() const override
	{
		return {
			make_method("add", &math::add, {"a", "b"}),
			make_method("sub", &math::sub, {"a", "b"}),
			make_method("mul", &math::mul, {"a", "b"}),
			make_method("div", &math::div, {"a", "b"}),
			make_method("exponentiate", &math::exponentiate, {"base", "expo"})
		};
	}

	double add(double a, std::optional<double> b) { return a + b.value_or(0); }
	double sub(double a, std::optional<double> b) { return a - b.value_or(0); }
	double mul(double a, std::optional<double> b) { return a * b.value_or(1); }
	double div(double a, std::optional<double> b) { return a / b.value_or(1); }
	double exponentiate(double base, double expo) { return std::pow(base, expo); }
};

REGISTER_SERVICE(math);
